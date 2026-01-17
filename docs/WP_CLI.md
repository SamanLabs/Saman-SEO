# WP-CLI Commands

Complete documentation for managing Saman SEO via WP-CLI.

---

## Table of Contents

- [Installation & Requirements](#installation--requirements)
- [Redirect Management](#redirect-management)
  - [List Redirects](#list-redirects)
  - [Export Redirects](#export-redirects)
  - [Import Redirects](#import-redirects)
- [Examples & Use Cases](#examples--use-cases)
- [Automation & Scripts](#automation--scripts)

---

## Installation & Requirements

### Requirements

- WP-CLI 2.0 or higher
- Saman SEO activated
- SSH/terminal access to your server

### Verify WP-CLI Installation

```bash
wp --version
```

### Verify Plugin Installation

```bash
wp plugin list
```

Look for `wp-seo-pilot` in the list with status `active`.

---

## Redirect Management

All redirect commands are namespaced under `wp wpseopilot redirects`.

**Location:** `includes/class-wpseopilot-service-cli.php`

---

### List Redirects

Display all redirects in your site.

**Command:**

```bash
wp wpseopilot redirects list [--format=<format>]
```

**Options:**

| Option | Values | Default | Description |
|--------|--------|---------|-------------|
| `--format` | `table`, `json`, `csv`, `yaml`, `count` | `table` | Output format |

---

#### Table Format (Default)

```bash
wp wpseopilot redirects list
```

**Output:**

```
+----+---------------+--------------+-------------+------+---------------------+
| ID | Source        | Target       | Status Code | Hits | Last Hit            |
+----+---------------+--------------+-------------+------+---------------------+
| 1  | /old-page     | /new-page    | 301         | 42   | 2025-12-15 10:30:00 |
| 2  | /promo        | /special     | 302         | 15   | 2025-12-14 08:45:00 |
| 3  | /legacy-url   | /current-url | 301         | 128  | 2025-12-16 14:20:00 |
+----+---------------+--------------+-------------+------+---------------------+
```

---

#### JSON Format

```bash
wp wpseopilot redirects list --format=json
```

**Output:**

```json
[
  {
    "id": "1",
    "source": "/old-page",
    "target": "/new-page",
    "status_code": "301",
    "hits": "42",
    "last_hit": "2025-12-15 10:30:00"
  },
  {
    "id": "2",
    "source": "/promo",
    "target": "/special",
    "status_code": "302",
    "hits": "15",
    "last_hit": "2025-12-14 08:45:00"
  }
]
```

---

#### CSV Format

```bash
wp wpseopilot redirects list --format=csv
```

**Output:**

```
id,source,target,status_code,hits,last_hit
1,/old-page,/new-page,301,42,2025-12-15 10:30:00
2,/promo,/special,302,15,2025-12-14 08:45:00
```

---

#### Count Format

```bash
wp wpseopilot redirects list --format=count
```

**Output:**

```
3
```

---

### Export Redirects

Export all redirects to a JSON file for backup or migration.

**Command:**

```bash
wp wpseopilot redirects export <file>
```

**Arguments:**

| Argument | Required | Description |
|----------|----------|-------------|
| `<file>` | Yes | Destination file path |

---

#### Basic Export

```bash
wp wpseopilot redirects export redirects.json
```

**Output:**

```
Success: Exported 15 redirects to redirects.json
```

---

#### Export to Specific Path

```bash
wp wpseopilot redirects export /backups/redirects-2025-12-15.json
```

---

#### Export File Format

**redirects.json:**

```json
[
  {
    "source": "/old-page",
    "target": "/new-page",
    "status_code": 301,
    "hits": 42,
    "last_hit": "2025-12-15 10:30:00"
  },
  {
    "source": "/promo",
    "target": "/special",
    "status_code": 302,
    "hits": 15,
    "last_hit": "2025-12-14 08:45:00"
  }
]
```

---

### Import Redirects

Import redirects from a JSON file.

**Command:**

```bash
wp wpseopilot redirects import <file>
```

**Arguments:**

| Argument | Required | Description |
|----------|----------|-------------|
| `<file>` | Yes | Source JSON file path |

---

#### Basic Import

```bash
wp wpseopilot redirects import redirects.json
```

**Output:**

```
Processing redirects...
Created redirect: /old-page → /new-page
Created redirect: /promo → /special
Success: Imported 2 redirects.
```

---

#### Import from Remote URL

```bash
# Download first
curl -o redirects.json https://example.com/redirects.json

# Then import
wp wpseopilot redirects import redirects.json
```

---

#### Import File Format

The import file must be valid JSON with this structure:

```json
[
  {
    "source": "/old-url",
    "target": "/new-url",
    "status_code": 301
  },
  {
    "source": "/another-old",
    "target": "https://external.com/page",
    "status_code": 302
  }
]
```

**Required Fields:**
- `source` (string) - Source path
- `target` (string) - Target URL
- `status_code` (int) - HTTP status code (301, 302, 307, 308)

**Optional Fields:**
- `hits` (int) - Hit count (defaults to 0)
- `last_hit` (string) - Last hit timestamp

---

## Examples & Use Cases

### Example 1: Backup Before Making Changes

```bash
# Export current redirects
wp wpseopilot redirects export backup-$(date +%Y%m%d).json

# Make changes...

# If needed, restore from backup
wp wpseopilot redirects import backup-20251215.json
```

---

### Example 2: Migrate Redirects Between Sites

**On Source Site:**

```bash
# Export redirects
wp wpseopilot redirects export production-redirects.json

# Download file
scp production-redirects.json user@staging-server:/tmp/
```

**On Destination Site:**

```bash
# Import redirects
wp wpseopilot redirects import /tmp/production-redirects.json
```

---

### Example 3: Bulk Redirect Creation

Create a JSON file with all your redirects:

**bulk-redirects.json:**

```json
[
  { "source": "/old-1", "target": "/new-1", "status_code": 301 },
  { "source": "/old-2", "target": "/new-2", "status_code": 301 },
  { "source": "/old-3", "target": "/new-3", "status_code": 301 },
  { "source": "/promo-2024", "target": "/promo-2025", "status_code": 302 }
]
```

Import:

```bash
wp wpseopilot redirects import bulk-redirects.json
```

---

### Example 4: Find Most Popular Redirects

```bash
# Export as JSON
wp wpseopilot redirects list --format=json > redirects.json

# Process with jq to find top redirects by hits
cat redirects.json | jq -r 'sort_by(.hits | tonumber) | reverse | .[0:10] | .[] | "\(.hits) hits: \(.source) → \(.target)"'
```

**Output:**

```
128 hits: /legacy-url → /current-url
42 hits: /old-page → /new-page
15 hits: /promo → /special
```

---

### Example 5: Convert CSV to Import Format

If you have redirects in CSV format:

**redirects.csv:**

```
source,target,status_code
/old-a,/new-a,301
/old-b,/new-b,301
```

Convert to JSON:

```bash
# Using csvtojson (install: npm install -g csvtojson)
csvtojson redirects.csv > redirects.json

# Import
wp wpseopilot redirects import redirects.json
```

---

### Example 6: Audit Redirect Performance

```bash
# Get all redirects as CSV
wp wpseopilot redirects list --format=csv > redirects-audit.csv

# Open in spreadsheet software for analysis
# Or process with command-line tools:

# Count redirects by status code
awk -F',' 'NR>1 {count[$4]++} END {for (code in count) print code": "count[code]}' redirects-audit.csv
```

---

### Example 7: Clean Up Unused Redirects

```bash
# List redirects with 0 hits (not triggered)
wp wpseopilot redirects list --format=csv | awk -F',' '$5 == 0 {print $0}'

# Manual review and cleanup via admin or database
```

---

## Automation & Scripts

### Automated Daily Backup

Add to cron:

```bash
# Edit crontab
crontab -e

# Add daily backup at 2 AM
0 2 * * * cd /var/www/html && wp wpseopilot redirects export /backups/redirects-$(date +\%Y\%m\%d).json
```

---

### Deployment Script

**deploy-redirects.sh:**

```bash
#!/bin/bash

# Deploy redirects from staging to production

# Backup production redirects first
echo "Backing up production redirects..."
wp wpseopilot redirects export /backups/prod-redirects-$(date +%Y%m%d-%H%M%S).json --path=/var/www/production

# Export from staging
echo "Exporting staging redirects..."
wp wpseopilot redirects export /tmp/staging-redirects.json --path=/var/www/staging

# Import to production
echo "Importing to production..."
wp wpseopilot redirects import /tmp/staging-redirects.json --path=/var/www/production

echo "Deployment complete!"
```

Make executable:

```bash
chmod +x deploy-redirects.sh
./deploy-redirects.sh
```

---

### Sync Script with Remote Server

**sync-redirects.sh:**

```bash
#!/bin/bash

REMOTE_USER="user"
REMOTE_HOST="production-server.com"
REMOTE_PATH="/var/www/html"
LOCAL_PATH="/var/www/staging"

# Export from remote
echo "Exporting from production..."
ssh $REMOTE_USER@$REMOTE_HOST "cd $REMOTE_PATH && wp wpseopilot redirects export /tmp/prod-redirects.json"

# Download
echo "Downloading..."
scp $REMOTE_USER@$REMOTE_HOST:/tmp/prod-redirects.json /tmp/

# Import locally
echo "Importing to staging..."
cd $LOCAL_PATH && wp wpseopilot redirects import /tmp/prod-redirects.json

echo "Sync complete!"
```

---

### Monitor Redirect Changes

**check-redirects.sh:**

```bash
#!/bin/bash

# Monitor redirect count and alert on changes

CURRENT_COUNT=$(wp wpseopilot redirects list --format=count)
LAST_COUNT=$(cat /tmp/redirect-count.txt 2>/dev/null || echo 0)

if [ "$CURRENT_COUNT" -ne "$LAST_COUNT" ]; then
    echo "Redirect count changed: $LAST_COUNT → $CURRENT_COUNT"

    # Send notification (example with mail)
    echo "Redirect count changed from $LAST_COUNT to $CURRENT_COUNT" | mail -s "Redirect Alert" admin@example.com

    # Update stored count
    echo $CURRENT_COUNT > /tmp/redirect-count.txt
fi
```

---

## Programmatic Usage in PHP

While WP-CLI commands are for terminal use, you can achieve similar functionality programmatically:

```php
// Get all redirects
global $wpdb;
$table = $wpdb->prefix . 'wpseopilot_redirects';
$redirects = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY hits DESC" );

// Export as JSON
$json = json_encode( $redirects, JSON_PRETTY_PRINT );
file_put_contents( 'redirects.json', $json );

// Import from JSON
$json = file_get_contents( 'redirects.json' );
$redirects = json_decode( $json, true );

foreach ( $redirects as $redirect ) {
    wpseopilot_create_redirect(
        $redirect['source'],
        $redirect['target'],
        $redirect['status_code']
    );
}
```

---

## Troubleshooting

### Command Not Found

**Error:**

```
Error: 'wpseopilot' is not a registered wp command.
```

**Solution:**

1. Verify plugin is activated:
   ```bash
   wp plugin list
   ```

2. Activate if needed:
   ```bash
   wp plugin activate wp-seo-pilot
   ```

3. Check WP-CLI cache:
   ```bash
   wp cli cache clear
   ```

---

### Import Fails

**Error:**

```
Error: Failed to import redirects.
```

**Solution:**

1. Verify JSON format:
   ```bash
   cat redirects.json | jq .
   ```

2. Check file permissions:
   ```bash
   chmod 644 redirects.json
   ```

3. Validate JSON structure matches required format

---

### Permission Denied

**Error:**

```
Error: Permission denied
```

**Solution:**

```bash
# Run as correct user
sudo -u www-data wp wpseopilot redirects list

# Or fix permissions
chown www-data:www-data redirects.json
```

---

## Best Practices

### 1. Always Backup Before Importing

```bash
wp wpseopilot redirects export backup.json
wp wpseopilot redirects import new-redirects.json
```

### 2. Use Version Control for Redirect Files

```bash
git add redirects.json
git commit -m "Update redirects"
git push
```

### 3. Schedule Regular Backups

Add to cron for automated backups.

### 4. Document Redirect Changes

Include comments in commit messages when updating redirect files.

### 5. Test Imports on Staging

Always test imports on staging before production.

---

## Related Documentation

- **[Redirect Manager](REDIRECTS.md)** - Redirect management guide
- **[Developer Guide](DEVELOPER_GUIDE.md)** - Programmatic redirect creation
- **[Getting Started](GETTING_STARTED.md)** - Basic plugin usage

---

## External Resources

- **[WP-CLI Official Documentation](https://wp-cli.org/)**
- **[WP-CLI Commands Cookbook](https://developer.wordpress.org/cli/commands/)**

---

**For more help, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
