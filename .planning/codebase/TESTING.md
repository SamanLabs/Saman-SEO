# Testing

> Generated: 2026-01-16
> Plugin: Saman SEO

## Current State

**Status:** No formal automated testing framework implemented

The codebase currently lacks:
- PHPUnit test suite
- Integration tests
- E2E tests for React admin
- Code coverage reporting
- Pre-commit hooks

## Test Infrastructure

### What Exists

| Component | Status | Location |
|-----------|--------|----------|
| PHPUnit binaries | Present but unused | `vendor/phpunit/` |
| Test configuration | Missing | No `phpunit.xml` |
| Test directory | Missing | No `tests/` folder |
| CI test jobs | Build only, no tests | `.github/workflows/` |

### GitHub Actions Workflows

**Beta Release** (`.github/workflows/beta-release.yml`):
- Triggers on push to `dev`, `beta`, `develop` branches
- Builds assets with npm
- Creates pre-release on GitHub
- **No test execution**

**Production Release** (`.github/workflows/release.yml`):
- Triggers on push to `main` branch
- Syncs version constant
- Creates GitHub release
- **No test execution**

## Testing Guidelines (from CONTRIBUTING.md)

### Manual Testing Requirements

Before submitting PRs, contributors should:

1. **Environment Testing**
   - Test on clean WordPress installation
   - Test on PHP 7.4, 8.0, and 8.1
   - Test on WordPress 5.8+ and latest version

2. **Theme Compatibility**
   - Twenty Twenty-Four
   - Astra
   - GeneratePress

3. **Plugin Compatibility**
   - WooCommerce
   - Contact forms
   - Other common plugins

4. **Editor Testing**
   - Gutenberg editor
   - Classic Editor

### Pre-PR Checklist

```
- [ ] Code follows WordPress Coding Standards
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors
- [ ] Tested on PHP 7.4, 8.0, and 8.1
- [ ] Tested on WordPress 5.8+ and latest version
- [ ] Works with Gutenberg and Classic Editor
- [ ] Responsive design (if UI changes)
- [ ] Accessibility considerations (WCAG 2.1 AA)
- [ ] No SQL injection vulnerabilities
- [ ] All user input is sanitized/validated
- [ ] Output is properly escaped
- [ ] Assets are minified (run npm run build)
- [ ] No new deprecation warnings
- [ ] Backward compatible (or breaking change documented)
- [ ] Documentation updated if needed
```

## Code Quality Tools

### Available

| Tool | Command | Purpose |
|------|---------|---------|
| ESLint | `npm run lint:js` | JavaScript linting |
| Prettier | `npm run format:js` | JavaScript formatting |

### Not Configured

| Tool | Status |
|------|--------|
| PHPCS | Mentioned but no `.phpcs.xml` |
| PHPStan | Not present |
| Pre-commit hooks | Not configured |

## Recommended Test Structure

For future implementation:

```
tests/
├── bootstrap.php              # WordPress test bootstrap
├── unit/                      # Unit tests
│   ├── test-helpers.php
│   ├── test-plugin.php
│   └── Api/
│       ├── test-rest-controller.php
│       └── test-dashboard-controller.php
├── integration/               # Integration tests
│   ├── test-redirects.php
│   ├── test-schema.php
│   └── test-sitemap.php
└── e2e/                       # End-to-end tests
    └── cypress/               # Cypress tests for React admin
```

### phpunit.xml Configuration

```xml
<?xml version="1.0"?>
<phpunit
    bootstrap="tests/bootstrap.php"
    backupGlobals="false"
    colors="true"
    convertErrorsToExceptions="true"
    convertNoticesToExceptions="true"
    convertWarningsToExceptions="true"
>
    <testsuites>
        <testsuite name="unit">
            <directory>tests/unit</directory>
        </testsuite>
        <testsuite name="integration">
            <directory>tests/integration</directory>
        </testsuite>
    </testsuites>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">includes</directory>
        </include>
        <exclude>
            <directory>vendor</directory>
        </exclude>
    </coverage>
</phpunit>
```

## Testability Assessment

### Well-Structured for Testing

| Pattern | Location | Benefit |
|---------|----------|---------|
| Service Container | `class-wpseopilot-plugin.php` | Easy to mock services |
| Abstract Base Classes | `class-rest-controller.php` | Interface contracts |
| Helper Functions | `helpers.php` | Isolated, testable units |
| Dependency Injection | Some services | Swappable dependencies |

### Challenges for Testing

| Issue | Impact | Solution |
|-------|--------|----------|
| Tight coupling in some services | Hard to isolate | Refactor with DI |
| WordPress API dependencies | Requires WP test suite | Use wp-env or docker |
| Database queries | State management | Transactions/rollback |
| External API calls | Network dependencies | Mock HTTP responses |

## Testing Priorities

### High Priority (Recommended First)

1. **REST API Controllers** - Core data operations
   - `Dashboard_Controller` - Statistics
   - `Redirects_Controller` - CRUD operations
   - `Settings_Controller` - Configuration

2. **Helper Functions** - Pure functions, easy to test
   - `get_post_meta()`
   - `replace_template_variables()`
   - `calculate_keyphrase_density()`

3. **Security Functions** - Critical for plugin safety
   - Input sanitization
   - Output escaping
   - Permission checks

### Medium Priority

4. **Service Layer** - Business logic
   - `Redirect_Manager`
   - `Internal_Linking`
   - `JsonLD`

5. **Schema Generation** - Structured data
   - Video, Course, Book schemas
   - JSON-LD output validation

### Lower Priority

6. **Admin UI** - React components
   - E2E tests with Cypress
   - Visual regression testing

7. **Integration Tests**
   - WooCommerce integration
   - AI Pilot integration

## Test File Example

```php
<?php
/**
 * Tests for helper functions.
 *
 * @package WPSEOPilot\Tests
 */

namespace WPSEOPilot\Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase {

    public function test_get_option_returns_default_when_empty() {
        // Arrange
        $key = 'nonexistent_option';
        $default = 'test_default';

        // Act
        $result = \WPSEOPilot\Helpers\get_option( $key, $default );

        // Assert
        $this->assertEquals( $default, $result );
    }

    public function test_replace_template_variables_replaces_title() {
        // Arrange
        $template = '%title% | Site Name';
        $context = [ 'title' => 'My Post' ];

        // Act
        $result = \WPSEOPilot\Helpers\replace_template_variables( $template, $context );

        // Assert
        $this->assertStringContainsString( 'My Post', $result );
    }
}
```

## CI/CD Test Integration

Recommended GitHub Actions addition:

```yaml
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['7.4', '8.0', '8.1', '8.2']
        wordpress: ['5.8', '6.0', 'latest']

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: mysqli
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run tests
        run: ./vendor/bin/phpunit --coverage-text
```

## Coverage Goals

| Component | Target | Current |
|-----------|--------|---------|
| Helpers | 80% | 0% |
| REST Controllers | 70% | 0% |
| Services | 60% | 0% |
| Overall | 50% | 0% |
