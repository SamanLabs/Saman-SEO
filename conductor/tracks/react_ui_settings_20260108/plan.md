# Plan: Re-construction of the UI to React - Main Settings Pages

This plan outlines the steps to migrate the "Defaults" and "Search Appearance" settings pages to a new React-based UI, as detailed in `spec.md`.

## Phase 1: Environment Setup and Initial Scaffolding [checkpoint: 92ffadf]

This phase focuses on preparing the development environment for React development within the existing WordPress plugin structure.

- [x] Task: Set up the React build process (2d58d00)
    - [ ] Sub-task: Add a new script to `package.json` to transpile JSX and bundle the React application.
    - [ ] Sub-task: Configure the build tool (e.g., esbuild, webpack, or a pre-configured solution like `@wordpress/scripts`) to output a single bundled JS file.
- [x] Task: Create a placeholder React component (2d58d00)
    - [ ] Sub-task: Write a simple "Hello World" React component to serve as a placeholder.
- [x] Task: Enqueue the React script in WordPress (864a0d8)
    - [ ] Sub-task: Write the necessary PHP code to register and enqueue the compiled React JavaScript bundle on the "Defaults" admin page.
    - [ ] Sub-task: Verify that the "Hello World" component renders correctly on the "Defaults" admin page.
- [x] Task: Conductor - User Manual Verification 'Phase 1: Environment Setup and Initial Scaffolding' (Protocol in workflow.md) [checkpoint: 92ffadf]

## Phase 2: REST API Endpoint Development

This phase focuses on creating the necessary backend infrastructure for the React components to communicate with WordPress.

- [~] Task: Create REST API endpoint for "Defaults" settings
    - [ ] Sub-task: Write failing tests (e.g., PestPHP or PHPUnit) for the GET and POST/PUT endpoints for the "Defaults" settings.
    - [ ] Sub-task: Implement the PHP class and methods to register a custom REST API endpoint (e.g., `/wp-seo-pilot/v1/settings/defaults`).
    - [ ] Sub-task: Implement the `GET` callback to retrieve and return all "Defaults" settings as JSON, ensuring proper permissions checks.
    - [ ] Sub-task: Implement the `POST`/`PUT` callback to receive, validate, and save all "Defaults" settings, ensuring proper permissions and security checks (nonces).
    - [ ] Sub-task: Ensure all tests for the "Defaults" endpoint are passing.
- [ ] Task: Create REST API endpoint for "Search Appearance" settings
    - [ ] Sub-task: Write failing tests for the GET and POST/PUT endpoints for the "Search Appearance" settings.
    - [ ] Sub-task: Implement the PHP class and methods to register a custom REST API endpoint (e.g., `/wp-seo-pilot/v1/settings/search-appearance`).
    - [ ] Sub-task: Implement the `GET` callback to retrieve and return all "Search Appearance" settings as JSON.
    - [ ] Sub-task: Implement the `POST`/`PUT` callback to receive, validate, and save all "Search Appearance" settings.
    - [ ] Sub-task: Ensure all tests for the "Search Appearance" endpoint are passing.
- [ ] Task: Conductor - User Manual Verification 'Phase 2: REST API Endpoint Development' (Protocol in workflow.md)

## Phase 3: React Component Implementation

This phase involves building the frontend React components for the two settings pages.

- [ ] Task: Develop the "Defaults" page React component
    - [ ] Sub-task: Write failing component tests (e.g., using Jest and React Testing Library) that verify the component renders correctly with mock data and that user interactions work as expected.
    - [ ] Sub-task: Build the React components to render the UI for the "Defaults" settings page, matching the existing visual style.
    - [ ] Sub-task: Implement the logic to fetch data from the `/defaults` REST API endpoint on component mount and populate the form fields.
    - [ ] Sub-task: Implement the logic to handle form changes and submit the updated settings to the `/defaults` REST API endpoint.
    - [ ] Sub-task: Add inline tooltips and hints for all relevant settings.
    - [ ] Sub-task: Ensure all component tests are passing.
- [ ] Task: Develop the "Search Appearance" page React component
    - [ ] Sub-task: Write failing component tests for the "Search Appearance" component.
    - [ ] Sub-task: Build the React components to render the UI for the "Search Appearance" settings page.
    - [ ] Sub-task: Implement the logic to fetch data from the `/search-appearance` REST API endpoint.
    - [ ] Sub-task: Implement the logic to handle form changes and submit updated settings to the `/search-appearance` REST API endpoint.
    - [ ] Sub-task: Add inline tooltips and hints.
    - [ ] Sub-task: Ensure all component tests are passing.
- [ ] Task: Conductor - User Manual Verification 'Phase 3: React Component Implementation' (Protocol in workflow.md)
