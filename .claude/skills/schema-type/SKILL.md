---
name: schema-type
description: Add a new schema.org structured data type to the Saman SEO plugin. Use when adding support for new schema types like Recipe, Event, Product, etc.
argument-hint: [schema-type] [description]
---

# Add Schema Type

Add a new schema.org structured data type to the Saman SEO plugin.

## Arguments
- `$ARGUMENTS` should contain: schema type (e.g., "Recipe", "Event") and description

## Steps

1. **Research the schema type** at https://schema.org/{Type}:
   - Required properties
   - Recommended properties
   - Nested types

2. **Create the schema service** at `includes/Service/class-saman-seo-service-{type}-schema.php`:

```php
<?php
/**
 * Service: {Type} Schema
 *
 * Generates schema.org {Type} structured data.
 *
 * @package Saman\SEO\Service
 */

namespace Saman\SEO\Service;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * {Type}_Schema service class.
 *
 * Handles {Type} schema generation for posts.
 */
class {Type}_Schema {

    /**
     * Meta key for storing {type} schema data.
     *
     * @var string
     */
    const META_KEY = '_SAMAN_SEO_{type}_schema';

    /**
     * Boot the service.
     */
    public function boot() {
        // Add schema to JSON-LD output
        add_filter( 'SAMAN_SEO_jsonld_data', array( $this, 'add_schema' ), 10, 2 );

        // Register meta box for editor
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );

        // Save meta box data
        add_action( 'save_post', array( $this, 'save_meta_box' ) );

        // Register REST field
        add_action( 'rest_api_init', array( $this, 'register_rest_field' ) );
    }

    /**
     * Add {Type} schema to JSON-LD output.
     *
     * @param array $schemas Existing schemas.
     * @param int   $post_id Post ID.
     * @return array Modified schemas.
     */
    public function add_schema( $schemas, $post_id ) {
        $data = get_post_meta( $post_id, self::META_KEY, true );

        if ( empty( $data ) || empty( $data['enabled'] ) ) {
            return $schemas;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => '{Type}',
        );

        // Add required properties
        if ( ! empty( $data['name'] ) ) {
            $schema['name'] = sanitize_text_field( $data['name'] );
        }

        if ( ! empty( $data['description'] ) ) {
            $schema['description'] = sanitize_textarea_field( $data['description'] );
        }

        // Add optional properties
        if ( ! empty( $data['image'] ) ) {
            $schema['image'] = esc_url( $data['image'] );
        }

        // Add nested types if applicable
        // Example: author, organization, etc.

        /**
         * Filter the {Type} schema data.
         *
         * @param array $schema  The schema data.
         * @param int   $post_id The post ID.
         * @param array $data    The raw meta data.
         */
        $schema = apply_filters( 'SAMAN_SEO_{type}_schema', $schema, $post_id, $data );

        $schemas[] = $schema;

        return $schemas;
    }

    /**
     * Register meta box for {Type} schema.
     */
    public function register_meta_box() {
        $post_types = apply_filters(
            'SAMAN_SEO_{type}_schema_post_types',
            array( 'post', 'page' )
        );

        add_meta_box(
            'saman-seo-{type}-schema',
            __( '{Type} Schema', 'saman-seo' ),
            array( $this, 'render_meta_box' ),
            $post_types,
            'normal',
            'default'
        );
    }

    /**
     * Render the meta box.
     *
     * @param \WP_Post $post Current post.
     */
    public function render_meta_box( $post ) {
        $data = get_post_meta( $post->ID, self::META_KEY, true );
        $data = wp_parse_args( $data, $this->get_defaults() );

        wp_nonce_field( 'saman_seo_{type}_schema', 'saman_seo_{type}_schema_nonce' );

        ?>
        <div class="saman-seo-schema-fields">
            <p>
                <label>
                    <input type="checkbox" name="saman_seo_{type}[enabled]" value="1"
                        <?php checked( ! empty( $data['enabled'] ) ); ?> />
                    <?php esc_html_e( 'Enable {Type} Schema', 'saman-seo' ); ?>
                </label>
            </p>

            <p>
                <label for="saman_seo_{type}_name">
                    <?php esc_html_e( 'Name', 'saman-seo' ); ?>
                </label>
                <input type="text" id="saman_seo_{type}_name"
                    name="saman_seo_{type}[name]"
                    value="<?php echo esc_attr( $data['name'] ); ?>"
                    class="widefat" />
            </p>

            <p>
                <label for="saman_seo_{type}_description">
                    <?php esc_html_e( 'Description', 'saman-seo' ); ?>
                </label>
                <textarea id="saman_seo_{type}_description"
                    name="saman_seo_{type}[description]"
                    class="widefat" rows="3"><?php echo esc_textarea( $data['description'] ); ?></textarea>
            </p>

            <!-- Add more fields as needed -->
        </div>
        <?php
    }

    /**
     * Save meta box data.
     *
     * @param int $post_id Post ID.
     */
    public function save_meta_box( $post_id ) {
        // Verify nonce
        if ( ! isset( $_POST['saman_seo_{type}_schema_nonce'] ) ||
             ! wp_verify_nonce(
                 sanitize_text_field( wp_unslash( $_POST['saman_seo_{type}_schema_nonce'] ) ),
                 'saman_seo_{type}_schema'
             )
        ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Sanitize and save
        $data = array();

        if ( isset( $_POST['saman_seo_{type}'] ) && is_array( $_POST['saman_seo_{type}'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below.
            $input = wp_unslash( $_POST['saman_seo_{type}'] );

            $data['enabled']     = ! empty( $input['enabled'] );
            $data['name']        = isset( $input['name'] ) ? sanitize_text_field( $input['name'] ) : '';
            $data['description'] = isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '';
            // Sanitize more fields...
        }

        update_post_meta( $post_id, self::META_KEY, $data );
    }

    /**
     * Get default values.
     *
     * @return array Default values.
     */
    private function get_defaults() {
        return array(
            'enabled'     => false,
            'name'        => '',
            'description' => '',
            // Add more defaults...
        );
    }

    /**
     * Register REST API field.
     */
    public function register_rest_field() {
        register_rest_field(
            array( 'post', 'page' ),
            '{type}_schema',
            array(
                'get_callback'    => array( $this, 'get_rest_field' ),
                'update_callback' => array( $this, 'update_rest_field' ),
                'schema'          => array(
                    'type'        => 'object',
                    'description' => __( '{Type} schema data.', 'saman-seo' ),
                ),
            )
        );
    }

    /**
     * Get REST field value.
     *
     * @param array $post Post data.
     * @return array Schema data.
     */
    public function get_rest_field( $post ) {
        return get_post_meta( $post['id'], self::META_KEY, true ) ?: $this->get_defaults();
    }

    /**
     * Update REST field value.
     *
     * @param array    $value   New value.
     * @param \WP_Post $post    Post object.
     * @return bool Success.
     */
    public function update_rest_field( $value, $post ) {
        // Sanitize and save via REST
        return update_post_meta( $post->ID, self::META_KEY, $value );
    }
}
```

3. **Register the service** in `includes/class-saman-seo-plugin.php`:

```php
$this->register( '{type}-schema', new \Saman\SEO\Service\{Type}_Schema() );
```

4. **Add React UI** (optional) for the admin interface in `src-v2/`

5. **Document the schema** - Add usage examples

## Existing Schema Types

Reference existing schemas in `includes/Service/`:
- `Video_Schema`
- `Course_Schema`
- `Book_Schema`
- `Music_Schema`
- `Movie_Schema`
- `Restaurant_Schema`
- `Service_Schema`
- `Job_Posting_Schema`
- `Software_Schema`

## Schema.org Reference

Common schema types to implement:
- `Recipe` - Cooking recipes with ingredients and instructions
- `Event` - Events with dates, locations, and tickets
- `Product` - Products with prices and availability
- `Review` - Reviews with ratings
- `FAQPage` - FAQ pages (already have block)
- `HowTo` - How-to guides (already have block)
- `Person` - People profiles
- `Organization` - Business/organization info

## Best Practices

1. **Validate with Google** - Test at https://search.google.com/test/rich-results
2. **Include required fields** - Check schema.org for requirements
3. **Add filter hooks** - Allow customization via filters
4. **Sanitize all data** - Use appropriate sanitization functions
5. **Support REST API** - Enable programmatic access

## Example Usage

```
/schema-type Recipe Add Recipe schema support with ingredients, instructions, and nutrition info
```
