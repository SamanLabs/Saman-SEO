---
name: seo-service
description: Generate a new SEO service class following the Saman SEO plugin architecture. Use when creating new features that need a dedicated service, adding functionality to the plugin, or extending plugin capabilities.
argument-hint: [service-name] [description]
---

# Create New SEO Service

Generate a new service class for the Saman SEO plugin following established patterns.

## Arguments
- `$ARGUMENTS` should contain: service name (e.g., "schema-validator") and optional description

## Steps

1. **Analyze the request** to determine:
   - Service name (convert to proper class naming: `Schema_Validator` from "schema-validator")
   - Service purpose and main functionality
   - Required hooks (admin, frontend, or both)
   - Whether it needs a settings page or REST API

2. **Create the service file** at `includes/class-saman-seo-service-{name}.php`

3. **Follow this template structure**:

```php
<?php
/**
 * Service: {Service Name}
 *
 * @package Saman\SEO
 */

namespace Saman\SEO;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * {Service_Name} service class.
 *
 * {Description of what this service does.}
 */
class Service_{Service_Name} {

    /**
     * Boot the service.
     *
     * Called during plugin initialization. Register all hooks here.
     */
    public function boot() {
        // Check if feature is enabled (optional, for toggleable features)
        if ( ! \Saman\SEO\Helpers\module_enabled( '{module-key}' ) ) {
            return;
        }

        // Register hooks
        add_action( 'init', array( $this, 'init' ) );

        // Admin-only hooks
        if ( is_admin() ) {
            add_action( 'admin_init', array( $this, 'admin_init' ) );
        }
    }

    /**
     * Initialize the service.
     */
    public function init() {
        // Frontend initialization
    }

    /**
     * Admin initialization.
     */
    public function admin_init() {
        // Admin-specific initialization
    }
}
```

4. **Register the service** in `includes/class-saman-seo-plugin.php`:
   - Add to the `boot()` method's service registration list
   - Example: `$this->register( '{service-key}', new Service_{Service_Name}() );`

5. **If the service needs a toggle**, add the option:
   - Add to default options in `Plugin::activate()`
   - Document in the module system

## Patterns to Follow

- **Namespace**: Always use `namespace Saman\SEO;`
- **Hooks**: Register in `boot()`, implement in separate methods
- **Feature Toggle**: Use `\Saman\SEO\Helpers\module_enabled( 'key' )` for toggleable features
- **Capability Checks**: Use `current_user_can( 'manage_options' )` for admin functions
- **Sanitization**: Always sanitize inputs with `sanitize_text_field()`, `absint()`, etc.
- **Escaping**: Always escape outputs with `esc_html()`, `esc_attr()`, etc.

## Example Usage

```
/seo-service content-analyzer Analyzes post content for SEO improvements
```

This will create a new `Service_Content_Analyzer` class with the standard boilerplate.
