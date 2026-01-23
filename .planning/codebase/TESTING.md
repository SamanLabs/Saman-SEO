# Testing Patterns

**Analysis Date:** 2026-01-23

## Test Framework

**Status:** No automated testing framework currently configured

**Note:** The codebase contains zero test files (.test.js, .spec.js). No test runner (Jest, Vitest, Mocha) is installed or configured. Testing infrastructure is not yet established.

**Development Tools:**
- `wp-scripts` handles linting and formatting but not testing
- No test-related npm scripts in `package.json`

## Implications for Testing

**Current State:**
- All code is tested manually in WordPress admin interface
- No unit tests, integration tests, or E2E tests are automated
- No test coverage measurement in place

**Recommended Testing Approach:**
Given the WordPress plugin context using `wp-scripts`, the recommended approach would be:

1. **Jest** - Built into wp-scripts and WordPress plugin testing
2. **React Testing Library** - For testing React components (already in wp-scripts ecosystem)
3. **wp-env** - For local WordPress development and test environment

## Code Structure for Testability

Despite no existing tests, the codebase exhibits testability-friendly patterns:

**Functional Components:**
- All React components are functional components, making them easier to test
- Props-based configuration allows easy test setup

**Pure Functions:**
- Utility functions like those in `utils/analytics.js` are pure and isolated
- Easy to test in isolation: `trackEvent(category, action, name, value)`

**Separated Concerns:**
- Custom hooks (`useSettings`) separate data fetching from UI logic
- Modal components isolated from business logic
- Utility functions exported separately from UI

**Dependency Injection:**
- Callbacks passed as props (`onNavigate`, `onGenerate`, `onClose`)
- API calls abstracted via `apiFetch` (injectable in tests)
- Global settings accessed via `window.SamanSEOSettings` (mockable)

## Async Operations Pattern (Testable)

**Example from `App.js`:**
```javascript
const fetchDashboard = useCallback(async () => {
    setLoading(true);
    try {
        const res = await apiFetch({ path: '/saman-seo/v1/dashboard' });
        if (res.success) {
            setData(res.data);
        }
    } catch (error) {
        console.error('Failed to fetch dashboard:', error);
    } finally {
        setLoading(false);
    }
}, []);
```

**Would test for:**
- Loading state transitions (true -> false)
- Success response handling
- Error state management
- Console.error called on failure

## Mock Points

**What to Mock:**
- `@wordpress/api-fetch` calls (all data fetching)
- `window.SamanSEOSettings` global object
- `window._paq` for analytics tracking
- `document` methods for DOM operations
- Event listeners in `addEventListener`

**What NOT to Mock:**
- React component rendering
- Hook behavior (useState, useEffect, useCallback)
- User interactions (button clicks, form input)
- Component lifecycle

**Example Mocking Pattern (from codebase structure):**

For `utils/analytics.js`:
```javascript
// Would need to mock:
window._paq = jest.fn();
window.SamanSEODebug = true;

// Test: trackEvent calls _paq.push
// Test: analytics disabled when _paq undefined
// Test: errors in analytics don't break functionality
```

For `useSettings.js`:
```javascript
// Would need to mock:
jest.mock('@wordpress/api-fetch');

// Test: fetchSettings updates state correctly
// Test: error handling sets error state
// Test: saveSettings returns true/false
```

For `AiGenerateModal.js`:
```javascript
// Would need to mock:
window.SamanSEOSettings = {
    aiEnabled: true,
    aiProvider: 'wp-ai-pilot',
    aiPilot: { installed: true, settingsUrl: '...' }
};
jest.mock('@wordpress/api-fetch');

// Test: Modal not rendered when isOpen is false
// Test: Shows notice when aiEnabled is false
// Test: Generates content with custom prompt
// Test: Error message displayed on generation failure
```

## Manual Testing Checklist (Current Practice)

Based on code analysis, manual testing includes:

**Component Rendering:**
- Components render without errors
- Props-based variations display correctly
- Loading states show appropriate UI
- Error states display error messages

**API Integration:**
- apiFetch calls succeed and update state
- Error handling prevents app crashes
- Loading indicators shown during operations
- Retry functionality works (if implemented)

**Navigation:**
- View switching works correctly (App.js handles this)
- URL updates with history.pushState
- Admin menu highlighting updates
- Back button restores previous view

**Async Operations:**
- Buttons disabled during loading
- Forms cleared after successful submission
- Error messages dismissed appropriately
- Callbacks executed with correct parameters

**Modal Behavior:**
- Modals open/close correctly
- Overlay click closes modal
- Form data preserved until apply/close
- Clear operations reset state

## Error Recovery Patterns (Inherent Testing)

**Silent Error Handling:**
- Analytics failures never break functionality
- DOM operation guards prevent errors when running in non-browser context
- Form submissions fail gracefully with user feedback

**Example from `analytics.js`:**
```javascript
export const trackEvent = (category, action, name = null, value = null) => {
    if (!isAnalyticsEnabled()) return;

    try {
        // ...analytics code...
    } catch (e) {
        // Silently fail - analytics should never break functionality
    }
};
```

**Example from `App.js` (DOM checks):**
```javascript
const updateAdminMenuHighlight = useCallback((view) => {
    if (typeof document === 'undefined') {
        return;
    }
    // Safe to use document methods now
}, []);
```

## State Management Testing Points

**For Components with useState:**
- Initial state values
- State updates on prop changes
- Multiple state variables synchronized correctly
- Side effects triggered properly

**For Hooks (useSettings):**
- Settings fetched on mount
- Settings updated after save
- Error state cleared on success
- Loading states transition correctly

**For Context (AssistantProvider):**
- Context values accessible via useAssistant hook
- sendMessage updates messages array
- executeAction appends assistant response
- clearChat resets state to initial value

## Component Integration Testing Points

**Form Components:**
- Input changes update state
- Submit buttons trigger callbacks
- Validation prevents invalid submissions (if present)
- Errors displayed inline

**Modal Components:**
- Props control visibility
- onClose callback triggered on dismiss
- onGenerate callback triggered on apply
- Pre-population from currentValue works

**Page Components:**
- Load data on mount
- Handle loading state
- Display data correctly
- Navigation callbacks work

## Performance Considerations (For Testing)

**Code Splitting:**
- Pages lazy-loaded: Dashboard, SearchAppearance, Sitemap, Tools, etc.
- Test that lazy imports load when needed
- Suspense fallback displays while loading

**useCallback Dependencies:**
- Verify all dependencies in dependency arrays are correct
- Test that callbacks don't break with missing dependencies

**Example from `App.js`:**
```javascript
const handleNavigate = useCallback(
    (view) => {
        if (view === currentView) {
            return;
        }
        setCurrentView(view);
        // ... more logic
    },
    [currentView, updateAdminMenuHighlight]
);
```

**Test points:**
- Verify handleNavigate memoization prevents unnecessary re-renders
- Verify dependency array includes all dependencies

## Test Organization Recommendations

**Proposed Structure (if testing were to be added):**
```
src-v2/
├── components/
│   ├── Header.js
│   ├── Header.test.js          # Component tests
│   ├── AiGenerateModal.js
│   └── AiGenerateModal.test.js
├── hooks/
│   ├── useSettings.js
│   └── useSettings.test.js
├── utils/
│   ├── analytics.js
│   └── analytics.test.js
├── pages/
│   ├── Dashboard.js
│   └── Dashboard.test.js
└── __mocks__/
    ├── @wordpress/api-fetch.js
    └── apiFetch.js
```

**Convention if testing added:**
- Test file co-located with implementation file
- Suffix: `.test.js`
- Mock files in `__mocks__/` directory
- Setup file for global mocks and utilities

## Critical Testing Areas (High Priority)

If testing infrastructure were to be added, focus on:

1. **API Integration** (`useSettings`, all pages using apiFetch)
   - Mock apiFetch responses
   - Verify state updates from API data
   - Verify error handling

2. **Navigation** (`App.js`)
   - Verify view switching logic
   - URL parameter handling
   - Menu highlighting

3. **Modal Operations** (`AiGenerateModal.js`, others)
   - Generate request building
   - Response handling
   - Form state management

4. **Analytics** (`utils/analytics.js`)
   - Verify events tracked correctly
   - Verify analytics doesn't break functionality
   - Verify feature flags respected

5. **Custom Hooks** (`useSettings.js`)
   - Fetch behavior
   - Save behavior
   - Error handling

---

*Testing analysis: 2026-01-23*

**Note:** This document describes testable patterns in the codebase and recommendations for adding testing infrastructure. Currently, no automated tests are implemented.
