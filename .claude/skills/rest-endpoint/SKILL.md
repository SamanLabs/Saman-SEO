---
name: rest-endpoint
description: Create a new REST API endpoint controller for the Saman SEO plugin. Use when adding new API endpoints, creating backend functionality for React admin pages, or building AJAX-like functionality.
argument-hint: [endpoint-name] [description]
---

# Create REST API Endpoint

Generate a new REST API controller following the Saman SEO plugin patterns.

## Arguments
- `$ARGUMENTS` should contain: endpoint name (e.g., "keywords") and description

## Steps

1. **Analyze the request** to determine:
   - Endpoint name and route (e.g., `/saman-seo/v1/keywords`)
   - HTTP methods needed (GET, POST, PUT, DELETE)
   - Request/response schema
   - Permission requirements

2. **Create the controller** at `includes/Api/class-{name}-controller.php`

3. **Follow this template**:

```php
<?php
/**
 * REST API: {Name} Controller
 *
 * @package Saman\SEO\Api
 */

namespace Saman\SEO\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * {Name} REST API controller.
 */
class {Name}_Controller extends WP_REST_Controller {

    /**
     * Namespace for the REST API.
     *
     * @var string
     */
    protected $namespace = 'saman-seo/v1';

    /**
     * Resource name.
     *
     * @var string
     */
    protected $rest_base = '{endpoint-base}';

    /**
     * Register routes.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    'args'                => $this->get_collection_params(),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_item' ),
                    'permission_callback' => array( $this, 'create_item_permissions_check' ),
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => array(
                        'id' => array(
                            'description' => __( 'Unique identifier.', 'saman-seo' ),
                            'type'        => 'integer',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_item' ),
                    'permission_callback' => array( $this, 'update_item_permissions_check' ),
                    'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array( $this, 'delete_item' ),
                    'permission_callback' => array( $this, 'delete_item_permissions_check' ),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    /**
     * Check if a given request has access to get items.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public function get_items_permissions_check( $request ) {
        return current_user_can( 'manage_options' );
    }

    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error
     */
    public function get_items( $request ) {
        $items = array(); // Fetch items from database

        return new WP_REST_Response( $items, 200 );
    }

    /**
     * Check if a given request has access to create items.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return bool|WP_Error
     */
    public function create_item_permissions_check( $request ) {
        return current_user_can( 'manage_options' );
    }

    /**
     * Create one item.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response|WP_Error
     */
    public function create_item( $request ) {
        // Sanitize and validate input
        $params = $request->get_json_params();

        // Create item in database

        return new WP_REST_Response( array( 'success' => true ), 201 );
    }

    /**
     * Get item schema.
     *
     * @return array
     */
    public function get_item_schema() {
        return array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => '{name}',
            'type'       => 'object',
            'properties' => array(
                'id' => array(
                    'description' => __( 'Unique identifier.', 'saman-seo' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                // Add more properties
            ),
        );
    }
}
```

4. **Register the controller** in `includes/class-saman-seo-plugin.php`:
   - Add to the `boot()` method's API controller registration
   - Example:
   ```php
   $controller = new \Saman\SEO\Api\{Name}_Controller();
   $controller->register_routes();
   ```

5. **Add to autoloader** if using non-standard naming

## Patterns to Follow

- **Namespace**: `namespace Saman\SEO\Api;`
- **Extend**: Always extend `WP_REST_Controller`
- **Route Prefix**: `/saman-seo/v1/`
- **Permissions**: Use `current_user_can( 'manage_options' )` for admin endpoints
- **Sanitization**: Sanitize ALL input data
- **Response**: Return `WP_REST_Response` or `WP_Error`
- **Schema**: Define item schema for documentation

## Example Usage

```
/rest-endpoint keywords Manage focus keyphrases and keyword analysis
```
