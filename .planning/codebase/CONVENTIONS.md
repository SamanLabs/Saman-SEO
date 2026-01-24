# Coding Conventions

**Analysis Date:** 2026-01-23

## Naming Patterns

**Files:**
- React components: PascalCase (e.g., `Header.js`, `AiGenerateModal.js`, `SearchPreview.js`)
- Hooks: PascalCase with `use` prefix (e.g., `useSettings.js`)
- Utilities: camelCase (e.g., `analytics.js`)
- Entry points: `index.js`
- LESS stylesheets: kebab-case (e.g., `admin.less`, `editor.less`, `internal-linking.less`)

**Functions:**
- React functional components: PascalCase
- Exported utility functions: camelCase (e.g., `trackEvent`, `buildContextString`)
- Custom hooks: camelCase with `use` prefix (e.g., `useSettings`, `useAssistant`)
- Event handlers: `handle*` prefix (e.g., `handleGenerate`, `handleNavigate`, `handleClose`)
- Callback functions: `handle*` or descriptive camelCase (e.g., `updateAdminMenuHighlight`, `fetchSettings`)

**Variables:**
- State variables: camelCase (e.g., `isGenerating`, `currentView`, `showSetup`)
- Constants: UPPER_SNAKE_CASE for module-level constants (e.g., `SCORE_LEVELS`, `PRIORITY_ORDER`, `ACTION_VIEW_MAP`)
- React props: camelCase (e.g., `fieldType`, `variableValues`, `currentValue`)

**Types:**
- No TypeScript found in codebase; vanilla JavaScript with JSDoc annotations used for type documentation
- Object literals used for configuration (e.g., `SCORE_LEVELS`, `navItems`)

## Code Style

**Formatting:**
- Tool: `wp-scripts` (WordPress standard)
- Indentation: 4 spaces (observed in file structure)
- Line length: No strict limit observed, but typically kept reasonable
- Semicolons: Required (enforced by wp-scripts)
- Quotes: Single quotes for strings (observed throughout)

**Linting:**
- Tool: `wp-scripts lint-js` (WordPress Scripts linter, extends ESLint)
- Run with: `npm run lint:js src-v2/`
- Format with: `npm run format:js src-v2/`

**Code Structure Patterns:**
- Functional components preferred
- Props destructuring at function signature level
- Conditional JSX: ternary operators and short-circuit evaluation
- Error handling: try-catch blocks in async operations
- Loading states: managed via useState with loading/saving flags

## Import Organization

**Order:**
1. React hooks and WordPress element imports: `import { useState, useEffect } from '@wordpress/element'`
2. Third-party packages: `import apiFetch from '@wordpress/api-fetch'`
3. Local components: `import Header from './components/Header'`
4. Local utilities/helpers
5. Styles: `import './index.css'`

**Path Aliases:**
- Relative imports used throughout (e.g., `./components/Header`)
- No @ alias or path mapping configured
- Lazy imports for route-based code splitting: `const Dashboard = lazy(() => import('./pages/Dashboard'))`

**Example from `App.js`:**
```javascript
import { lazy, Suspense, useCallback, useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import Header from './components/Header';
import './index.css';

const Dashboard = lazy(() => import('./pages/Dashboard'));
```

## Error Handling

**Patterns:**
- Try-catch blocks for async operations (apiFetch calls)
- Error state managed in useState: `const [error, setError] = useState(null)`
- Error cleared on successful operations: `setError(null)`
- Console logging for error tracking: `console.error('Failed to fetch:', error)`
- User-friendly fallback messages: `err.message || 'Failed to save settings'`
- Silent failures for non-critical operations like analytics or menu highlighting
- Null checks before DOM operations: `if (typeof document === 'undefined') return;`

**Example from `useSettings.js`:**
```javascript
const fetchSettings = useCallback(async () => {
    try {
        setLoading(true);
        const response = await apiFetch({
            path: '/saman-seo/v1/settings',
        });
        setSettings(response.data || {});
        setError(null);
    } catch (err) {
        console.error('Failed to fetch settings:', err);
        setError(err.message || 'Failed to fetch settings');
    } finally {
        setLoading(false);
    }
}, []);
```

**Example from `App.js` (silent failure):**
```javascript
try {
    const response = await apiFetch({ path: '/saman-seo/v1/setup/status' });
    if (response.success && response.data.show_wizard) {
        setShowSetup(true);
    }
} catch (err) {
    // Ignore errors, just show the app
}
setSetupChecked(true);
```

## Logging

**Framework:** `console` object

**Patterns:**
- `console.error()` for error logging during API failures
- Always include context in error logs: `console.error('Failed to fetch settings:', err)`
- Optional debug mode: checks for `window.SamanSEODebug` flag before verbose logging
- Analytics utility has special handling: failures never break functionality (silently caught)

**Example from `utils/analytics.js`:**
```javascript
// Debug log in development
if (window.SamanSEODebug) {
    console.log('Saman SEO Analytics:', { category, action, name, value });
}
```

## Comments

**When to Comment:**
- File headers: Document module purpose and high-level functionality
- Complex logic: Explain "why" not "what"
- JSDoc comments for exported functions and components with parameter descriptions

**JSDoc/TSDoc:**
- Used for exported functions and components
- Parameter descriptions: `@param {type} name - Description`
- Return type hints: `@returns {type} - Description`

**Example from `SocialPreview.js`:**
```javascript
/**
 * Twitter/X Preview Card
 *
 * @param {Object} props
 * @param {string} props.image - Image URL
 * @param {string} props.title - Title text
 * @param {string} props.description - Description text
 * @param {string} props.domain - Site domain (e.g., "example.com")
 * @param {string} props.cardType - Card type: "summary" or "summary_large_image"
 */
export const TwitterPreview = ({ image, title, description, domain, cardType = 'summary_large_image' }) => {
```

## Function Design

**Size:** Functions kept focused and reasonably sized, typically 30-100 lines
- Components: 50-200 lines including JSX
- Hooks: 30-80 lines for utility functions
- Async handlers: 15-50 lines per operation

**Parameters:**
- Props destructured at component signature
- Callback functions passed as props (e.g., `onNavigate`, `onGenerate`, `onClose`)
- Options objects used for multiple related parameters
- Default parameters used for optional values

**Return Values:**
- React components return JSX
- Hooks return object with values and methods
- Utility functions return primitives or objects
- Null checks before rendering: `if (!isOpen) return null;` pattern used

**Example from `AiGenerateModal.js`:**
```javascript
const AiGenerateModal = ({
    isOpen,
    onClose,
    onGenerate,
    fieldType = 'title',
    currentValue = '',
    placeholder = '',
    variableValues = {},
    context = {},
}) => {
```

## Module Design

**Exports:**
- Default exports for main components: `export default Header;`
- Named exports for utility functions and sub-components: `export const FacebookPreview = (...)`
- Hooks exported as named exports: `export function useSettings() {}`

**Barrel Files:**
- Used selectively: `assistants/index.js` exports multiple agent components
- Not universally applied

**Example from `SocialPreview.js`:**
```javascript
export const FacebookPreview = ({ ... }) => { ... };
export const TwitterPreview = ({ ... }) => { ... };
export const SocialPreviews = ({ ... }) => { ... };
export default SocialPreviews;
```

## Global Configuration & Initialization

**Global Settings Access:**
- Plugin settings accessed via `window.SamanSEOSettings` object
- Feature flags passed at page load time (e.g., `aiEnabled`, `aiProvider`)

**Example from `AiGenerateModal.js`:**
```javascript
const globalSettings = window?.SamanSEOSettings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
```

## React-Specific Patterns

**Hooks Usage:**
- `useState` for local component state
- `useEffect` for side effects with proper cleanup
- `useCallback` for memoized callbacks passed as props
- `Suspense` and `lazy()` for code-splitting pages

**Context API:**
- Used in `AssistantProvider.js` for sharing chat state
- Custom hooks created to consume context: `useAssistant()`
- Context used for features requiring deep component tree sharing

**State Management:**
- Props drilling used for navigation callbacks
- Context API for assistant chat state
- Individual useState calls for each piece of state (not combined into single state object)
- Previous state updates with spread operator: `setSettings(prev => ({ ...prev, ...newSettings }))`

---

*Convention analysis: 2026-01-23*
