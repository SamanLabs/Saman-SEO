# Technology Stack: WP SEO Pilot

This document outlines the core technologies and tools used in the development and operation of the WP SEO Pilot plugin.

## Core Technologies

*   **Backend Language & Platform:**
    *   **PHP:** The primary programming language for the plugin's server-side logic and integration with the WordPress core.
    *   **WordPress:** The Content Management System (CMS) platform upon which the plugin is built, providing its ecosystem and core functionalities.

*   **Frontend Technologies:**
    *   **JavaScript:** Used for interactive elements and client-side logic.
    *   **React:** Planned for the modernization of the plugin's user interface, especially for the main settings pages, to enhance user experience and performance.
    *   **HTML:** For structuring web content within the WordPress environment.
    *   **CSS:** For styling the user interface.
    *   **Less:** A CSS pre-processor used for more efficient and organized stylesheet development.

## Development and Build Tools

*   **npm:** The package manager for JavaScript dependencies.
*   **lessc:** The Less compiler, used to transform Less stylesheets into standard CSS.
*   **concurrently:** A utility for running multiple commands concurrently, used in the asset build pipeline.
*   **rimraf:** A tool for removing files and folders, used in the clean script of the asset build pipeline.
