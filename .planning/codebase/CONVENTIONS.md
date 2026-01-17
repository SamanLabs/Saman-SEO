# Code Conventions

> Generated: 2026-01-16
> Plugin: Saman SEO

## Coding Style

### Indentation & Formatting

| Aspect | Convention | Example |
|--------|------------|---------|
| Indentation | Tabs (WordPress standard) | `\t` |
| Brace Style | K&R (opening brace same line) | `if ($x) {` |
| Line Length | <120 characters | - |
| Blank Lines | 1 between methods, 2 between sections | - |

### PHP Example

```php
<?php
/**
 * Service description.
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

class Example_Service {
	/**
	 * Configuration option.
	 *
	 * @var string
	 */
	private $option = '';

	/**
	 * Initialize the service.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'init', [ $this, 'register_hooks' ] );
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Hook registration
	}
}
```

## Naming Conventions

### Classes

| Type | Convention | Example |
|------|------------|---------|
| Services | `{Name}` with underscore words | `Video_Schema`, `Redirect_Manager` |
| Controllers | `{Name}_Controller` | `Dashboard_Controller` |
| Assistants | `{Name}_Assistant` | `General_SEO_Assistant` |
| Integrations | `{Name}` | `AI_Pilot`, `WooCommerce` |

### Methods

| Type | Convention | Example |
|------|------------|---------|
| Public | `snake_case` | `get_redirects()` |
| Private | `snake_case` | `format_redirect()` |
| Protected | `snake_case` | `permission_check()` |
| Getters | `get_*`, `is_*`, `has_*` | `get_settings()`, `is_enabled()` |
| Setters | `set_*`, `update_*` | `update_redirect()` |
| Actions | `handle_*`, `process_*` | `handle_request()` |
| WordPress | `register_*`, `enqueue_*` | `register_routes()` |

### Variables

| Type | Convention | Example |
|------|------------|---------|
| Local | `snake_case` | `$redirect_url` |
| Class Properties | `snake_case` | `$this->api_key` |
| Constants | `SCREAMING_SNAKE_CASE` | `WPSEOPILOT_VERSION` |
| Temporary | Descriptive `snake_case` | `$temp_data` |

### Files

| Type | Pattern | Example |
|------|---------|---------|
| Classes | `class-{slug}.php` | `class-wpseopilot-service-frontend.php` |
| Templates | `{feature}.php` | `settings-page.php` |
| LESS | `{feature}.less` | `admin.less` |
| React | `PascalCase.js` | `SearchPreview.js` |
| Hooks | `camelCase.js` | `useSettings.js` |

## Documentation Style

### File Header

```php
<?php
/**
 * Short description.
 *
 * Long description with more details about what this does,
 * why it exists, and how to use it.
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */
```

### Class Documentation

```php
/**
 * Main plugin orchestrator.
 *
 * Manages service registration and provides container access.
 */
class Plugin { ... }
```

### Method Documentation

```php
/**
 * Return a success response.
 *
 * @param mixed  $data    Response data.
 * @param string $message Optional message.
 * @return \WP_REST_Response
 */
protected function success( $data = null, $message = '' ) { ... }
```

### Function Documentation

```php
/**
 * Fetch SEO meta for a post with sane defaults.
 *
 * @param int|WP_Post $post Post or ID.
 *
 * @return array{
 *     title:string,
 *     description:string,
 *     canonical:string,
 *     noindex:string,
 *     nofollow:string,
 *     og_image:string
 * }
 */
function get_post_meta( $post ) { ... }
```

### PHPDoc Tags Used

| Tag | Usage |
|-----|-------|
| `@package` | Always at file/class level |
| `@since` | Version introduced (0.2.0, etc.) |
| `@param` | Parameter type and description |
| `@return` | Return type |
| `@throws` | Exceptions (if applicable) |

## Common Patterns

### Singleton Pattern

```php
class Plugin {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Private constructor
	}
}

// Usage
$plugin = \WPSEOPilot\Plugin::instance();
```

### Service Container Pattern

```php
// Registration
$this->register( 'settings', new Service\Settings() );

// Registration method
private function register( $key, $service ) {
	if ( method_exists( $service, 'boot' ) ) {
		$service->boot();
	}
	$this->services[ $key ] = $service;
}

// Retrieval
public function get( $key ) {
	return $this->services[ $key ] ?? null;
}
```

### Abstract Base Class Pattern

```php
abstract class REST_Controller {
	protected $namespace = 'wpseopilot/v2';

	abstract public function register_routes();

	protected function success( $data = null, $message = '' ) {
		return rest_ensure_response( [
			'success' => true,
			'data'    => $data,
			'message' => $message,
		] );
	}

	protected function error( $message, $code = 'error', $status = 400 ) {
		return new \WP_Error( $code, $message, [ 'status' => $status ] );
	}
}
```

### Early Exit Pattern

```php
public function handle_request() {
	if ( ! $this->is_enabled() ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Main logic here
}
```

### ABSPATH Guard

```php
// Required at top of every PHP file
defined( 'ABSPATH' ) || exit;
```

## Security Patterns

### Input Sanitization

```php
// Text input
$title = sanitize_text_field( $_POST['title'] );

// Keys and slugs
$key = sanitize_key( $data['id'] );

// Multiline text
$description = sanitize_textarea_field( $data['description'] );

// URLs
$url = esc_url_raw( $data['url'] );

// Hex colors
$color = sanitize_hex_color( $data['color'] );

// Integers
$count = absint( $_GET['per_page'] );
```

### Output Escaping

```php
// HTML text
echo esc_html( $title );

// Localized output
esc_html_e( 'Label', 'wp-seo-pilot' );

// URLs in HTML
echo esc_url( $url );

// HTML attributes
echo esc_attr( $value );

// HTML with allowed tags
echo wp_kses_post( $html );
```

### Database Queries

```php
// Always use prepare for variables
$results = $wpdb->get_results( $wpdb->prepare(
	"SELECT * FROM {$wpdb->posts} WHERE post_status = %s",
	'publish'
) );
```

### REST API Argument Validation

```php
'args' => [
	'search' => [
		'required'          => false,
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
	],
	'per_page' => [
		'required'          => false,
		'type'              => 'integer',
		'default'           => 50,
		'sanitize_callback' => 'absint',
	],
],
```

## Error Handling

### WP_Error Usage

```php
protected function error( $message, $code = 'error', $status = 400 ) {
	return new \WP_Error( $code, $message, [ 'status' => $status ] );
}

// Usage
if ( ! $item ) {
	return $this->error( __( 'Entry not found.', 'wp-seo-pilot' ), 'not_found', 404 );
}
```

### Method/Class Existence Checks

```php
// Before calling optional method
if ( method_exists( $service, 'boot' ) ) {
	$service->boot();
}

// Before using optional class
if ( class_exists( '\WPSEOPilot\Integration\AI_Pilot' ) ) {
	\WPSEOPilot\Integration\AI_Pilot::init();
}
```

## WordPress Integration

### Hook Registration

```php
public function boot() {
	// Actions
	add_action( 'init', [ $this, 'register_hooks' ] );
	add_action( 'wp_head', [ $this, 'render_head' ], 1 );
	add_action( 'rest_api_init', [ $this, 'register_routes' ] );

	// Filters
	add_filter( 'pre_get_document_title', [ $this, 'filter_title' ], 0 );
	add_filter( 'post_row_actions', [ $this, 'add_row_actions' ], 10, 2 );
}
```

### AJAX Handlers

```php
public function boot() {
	add_action( 'wp_ajax_wpseopilot_generate', [ $this, 'ajax_generate' ] );
}

public function ajax_generate() {
	check_ajax_referer( 'wpseopilot_nonce', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( 'Unauthorized' );
	}

	// Process request
	wp_send_json_success( $data );
}
```

### Options API

```php
// Get with default
$value = get_option( 'wpseopilot_setting', 'default' );

// Update
update_option( 'wpseopilot_setting', $value );

// Delete
delete_option( 'wpseopilot_setting' );
```

### Post Meta

```php
// Single meta key stores serialized JSON
$meta = get_post_meta( $post_id, '_wpseopilot_meta', true );
$meta = $meta ? json_decode( $meta, true ) : [];

// Update
update_post_meta( $post_id, '_wpseopilot_meta', wp_json_encode( $meta ) );
```

## JavaScript/React Conventions

### Component Structure

```jsx
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const SearchPreview = ({ postId }) => {
	const [data, setData] = useState(null);

	useEffect(() => {
		apiFetch({ path: `/wpseopilot/v2/meta/${postId}` })
			.then(setData);
	}, [postId]);

	return (
		<div className="wpseopilot-preview">
			{/* Component content */}
		</div>
	);
};

export default SearchPreview;
```

### Hook Pattern

```jsx
// useSettings.js
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export const useSettings = () => {
	const [settings, setSettings] = useState({});
	const [loading, setLoading] = useState(true);

	useEffect(() => {
		apiFetch({ path: '/wpseopilot/v2/settings' })
			.then(setSettings)
			.finally(() => setLoading(false));
	}, []);

	return { settings, loading };
};
```

### CSS Class Naming

```css
/* BEM-ish naming with plugin prefix */
.wpseopilot-panel { }
.wpseopilot-panel__header { }
.wpseopilot-panel__content { }
.wpseopilot-panel--active { }

/* Component-specific */
.wpseopilot-search-preview { }
.wpseopilot-search-preview__title { }
.wpseopilot-search-preview__url { }
```
