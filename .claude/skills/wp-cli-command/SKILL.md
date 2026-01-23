---
name: wp-cli-command
description: Add a new WP-CLI command to the Saman SEO plugin. Use when adding command-line functionality, automation tools, or developer utilities.
argument-hint: [command-name] [description]
---

# Add WP-CLI Command

Add a new WP-CLI command to the Saman SEO plugin CLI interface.

## Arguments
- `$ARGUMENTS` should contain: command name (e.g., "audit") and description

## Steps

1. **Analyze the command requirements**:
   - Command name and subcommands
   - Required and optional arguments
   - Output format (table, json, progress bar)
   - Destructive operations needing confirmation

2. **Add to CLI service** at `includes/class-saman-seo-service-cli.php`:

```php
/**
 * {Command description}.
 *
 * ## OPTIONS
 *
 * [<subcommand>]
 * : The subcommand to run.
 * ---
 * options:
 *   - list
 *   - get
 *   - create
 *   - delete
 * ---
 *
 * [--id=<id>]
 * : The item ID.
 *
 * [--format=<format>]
 * : Output format.
 * ---
 * default: table
 * options:
 *   - table
 *   - json
 *   - csv
 * ---
 *
 * ## EXAMPLES
 *
 *     # List all items
 *     $ wp saman-seo {command} list
 *
 *     # Get a specific item
 *     $ wp saman-seo {command} get --id=123
 *
 *     # Export to JSON
 *     $ wp saman-seo {command} list --format=json
 *
 * @param array $args       Positional arguments.
 * @param array $assoc_args Associative arguments.
 */
public function {command}( $args, $assoc_args ) {
    $subcommand = isset( $args[0] ) ? $args[0] : 'list';
    $format     = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

    switch ( $subcommand ) {
        case 'list':
            $this->{command}_list( $assoc_args );
            break;

        case 'get':
            $id = \WP_CLI\Utils\get_flag_value( $assoc_args, 'id' );
            if ( ! $id ) {
                \WP_CLI::error( __( 'Please provide --id parameter.', 'saman-seo' ) );
            }
            $this->{command}_get( $id, $format );
            break;

        case 'create':
            $this->{command}_create( $assoc_args );
            break;

        case 'delete':
            $id = \WP_CLI\Utils\get_flag_value( $assoc_args, 'id' );
            if ( ! $id ) {
                \WP_CLI::error( __( 'Please provide --id parameter.', 'saman-seo' ) );
            }
            $this->{command}_delete( $id );
            break;

        default:
            \WP_CLI::error( sprintf(
                __( 'Unknown subcommand: %s', 'saman-seo' ),
                $subcommand
            ) );
    }
}

/**
 * List items.
 *
 * @param array $assoc_args Associative arguments.
 */
private function {command}_list( $assoc_args ) {
    $format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

    $items = array(); // Fetch items

    if ( empty( $items ) ) {
        \WP_CLI::warning( __( 'No items found.', 'saman-seo' ) );
        return;
    }

    \WP_CLI\Utils\format_items(
        $format,
        $items,
        array( 'id', 'name', 'status', 'created' )
    );
}

/**
 * Get single item.
 *
 * @param int    $id     Item ID.
 * @param string $format Output format.
 */
private function {command}_get( $id, $format ) {
    $item = null; // Fetch item by ID

    if ( ! $item ) {
        \WP_CLI::error( sprintf(
            __( 'Item not found: %d', 'saman-seo' ),
            $id
        ) );
    }

    if ( 'json' === $format ) {
        \WP_CLI::line( wp_json_encode( $item, JSON_PRETTY_PRINT ) );
    } else {
        \WP_CLI\Utils\format_items( 'table', array( $item ), array_keys( (array) $item ) );
    }
}
```

3. **Register the command** in the CLI service's `boot()` method:

```php
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    \WP_CLI::add_command( 'saman-seo {command}', array( $this, '{command}' ) );
}
```

4. **Document in WP_CLI.md**:

```markdown
### wp saman-seo {command}

{Description}

**Subcommands:**
- `list` - List all items
- `get` - Get a specific item
- `create` - Create a new item
- `delete` - Delete an item

**Options:**
- `--id=<id>` - Item ID
- `--format=<format>` - Output format (table, json, csv)

**Examples:**
```bash
wp saman-seo {command} list
wp saman-seo {command} get --id=123
wp saman-seo {command} list --format=json > export.json
```
```

## WP-CLI Utilities

```php
// Success message
\WP_CLI::success( 'Operation completed.' );

// Error and exit
\WP_CLI::error( 'Something went wrong.' );

// Warning (continues execution)
\WP_CLI::warning( 'This might be an issue.' );

// Simple output
\WP_CLI::line( 'Some text' );

// Progress bar
$progress = \WP_CLI\Utils\make_progress_bar( 'Processing', $count );
foreach ( $items as $item ) {
    // Process item
    $progress->tick();
}
$progress->finish();

// Confirmation prompt
\WP_CLI::confirm( 'Are you sure you want to delete this?' );

// Format items as table/json/csv
\WP_CLI\Utils\format_items( $format, $items, $columns );
```

## Existing Commands

Reference existing commands in `WP_CLI.md`:
- `wp saman-seo redirects` - Manage redirects (list, create, delete, import, export)

## Best Practices

1. **Use WP-CLI utilities** - `format_items()`, `make_progress_bar()`, etc.
2. **Support multiple formats** - table, json, csv for data export
3. **Confirm destructive actions** - Use `\WP_CLI::confirm()` before deletes
4. **Provide helpful errors** - Include context and suggestions
5. **Document thoroughly** - PHPDoc with examples

## Example Usage

```
/wp-cli-command audit Run SEO audits from command line with bulk processing
```
