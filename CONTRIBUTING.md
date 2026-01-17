# Contributing to Saman SEO

Thank you for your interest in contributing to Saman SEO! This project exists to establish an open standard for WordPress SEO, and community contributions are essential to that mission.

---

## Why Contribute?

Saman SEO is more than just another plugin—it's a movement toward transparency in SEO tooling. By contributing, you're:

- **Establishing Standards**: Helping define best practices for WordPress SEO
- **Improving Quality**: Making SEO more accessible and effective for everyone
- **Building Community**: Joining developers who believe in open collaboration over proprietary black boxes
- **Advancing Your Skills**: Working on real-world SEO challenges with experienced developers

---

## Ways to Contribute

### 🐛 Report Bugs

Found a bug? Please [open an issue](https://github.com/jhd3197/WP-SEO-Pilot/issues) with:

- **Clear description** of the problem
- **Steps to reproduce** the issue
- **Expected behavior** vs actual behavior
- **Environment details**: WordPress version, PHP version, theme, other active plugins
- **Screenshots or error logs** if applicable

### 💡 Suggest Features

Have an idea for improvement? [Open a feature request](https://github.com/jhd3197/WP-SEO-Pilot/issues) including:

- **Use case**: What problem does this solve?
- **Proposed solution**: How should it work?
- **Alternatives considered**: Other approaches you've thought about
- **Impact**: Who benefits from this feature?

### 📝 Improve Documentation

Documentation contributions are incredibly valuable:

- Fix typos or unclear explanations
- Add code examples
- Translate documentation
- Create tutorials or guides
- Improve inline code comments

### 🔧 Submit Code

Code contributions follow our standard workflow (see below).

### 💬 Join Discussions

Participate in [GitHub Discussions](https://github.com/jhd3197/WP-SEO-Pilot/discussions):

- Answer questions from other users
- Share your use cases and implementations
- Discuss proposed features
- Provide feedback on RFCs

---

## Development Setup

### Prerequisites

- PHP 7.4 or higher
- WordPress 5.8 or higher
- Node.js 14+ and npm (for asset compilation)
- Git
- Local WordPress development environment (Local, MAMP, Docker, etc.)

### Getting Started

1. **Fork the repository**
   ```bash
   # Visit https://github.com/jhd3197/WP-SEO-Pilot and click "Fork"
   ```

2. **Clone your fork**
   ```bash
   git clone https://github.com/YOUR-USERNAME/wp-seo-pilot.git
   cd wp-seo-pilot
   ```

3. **Add upstream remote**
   ```bash
   git remote add upstream https://github.com/jhd3197/WP-SEO-Pilot.git
   ```

4. **Install dependencies**
   ```bash
   npm install
   ```

5. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

---

## Development Workflow

### 1. Make Your Changes

**Code Style:**
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use tabs for indentation
- Use meaningful variable and function names
- Add inline comments for complex logic
- Write self-documenting code

**Testing:**
- Test your changes on a clean WordPress installation
- Test with popular themes (Twenty Twenty-Four, Astra, GeneratePress)
- Test with common plugins (WooCommerce, contact forms, etc.)
- Verify changes don't break existing functionality

**Asset Compilation:**
```bash
# During development
npm run watch

# Before committing
npm run build
```

### 2. Commit Your Changes

**Commit Message Format:**
```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Build process, dependencies, etc.

**Examples:**
```bash
git commit -m "feat(sitemaps): add video sitemap support"
git commit -m "fix(redirects): prevent infinite redirect loops"
git commit -m "docs(filters): add examples for og_image filter"
```

### 3. Push to Your Fork

```bash
git push origin feature/your-feature-name
```

### 4. Create Pull Request

1. Visit your fork on GitHub
2. Click "New Pull Request"
3. Select base: `main` and compare: `feature/your-feature-name`
4. Fill out the PR template:
   - **Description**: What does this PR do?
   - **Motivation**: Why is this change needed?
   - **Testing**: How have you tested this?
   - **Screenshots**: Visual changes? Include before/after
   - **Checklist**: Confirm you've followed guidelines

---

## Code Guidelines

### PHP Standards

```php
<?php
/**
 * Short description.
 *
 * Long description with details about what this does,
 * why it exists, and how to use it.
 *
 * @since 1.0.0
 *
 * @param string $param1 Description of parameter.
 * @param array  $param2 Description of parameter.
 *
 * @return bool True on success, false on failure.
 */
function wpseopilot_example_function( $param1, $param2 = [] ) {
	// Early return for invalid input
	if ( empty( $param1 ) ) {
		return false;
	}

	// Clear, descriptive variable names
	$sanitized_input = sanitize_text_field( $param1 );
	
	// Use filters to allow extensibility
	$result = apply_filters( 'wpseopilot_example_result', $sanitized_input, $param2 );
	
	return true;
}
```

### JavaScript Standards

```javascript
/**
 * Short description.
 *
 * @since 1.0.0
 *
 * @param {string} param1 Description.
 * @param {Object} param2 Description.
 * @return {boolean} Description.
 */
function exampleFunction( param1, param2 = {} ) {
	// Early return
	if ( ! param1 ) {
		return false;
	}

	// Use const/let, not var
	const result = processInput( param1 );
	
	return true;
}
```

### CSS/Less Standards

```less
// Use Less variables
@primary-color: #0073aa;
@border-radius: 4px;

// Organize by component
.wpseopilot-metabox {
	padding: 20px;
	border-radius: @border-radius;
	
	&__header {
		margin-bottom: 15px;
		font-weight: 600;
	}
	
	&__field {
		margin-bottom: 10px;
		
		label {
			display: block;
			margin-bottom: 5px;
		}
		
		input[type="text"] {
			width: 100%;
		}
	}
}
```

---

## Testing Checklist

Before submitting a PR, verify:

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
- [ ] Assets are minified (run `npm run build`)
- [ ] No new deprecation warnings
- [ ] Backward compatible (or breaking change documented)
- [ ] Documentation updated if needed

---

## Pull Request Review Process

### What to Expect

1. **Automated Checks**: GitHub Actions will run automated tests
2. **Code Review**: Maintainers will review your code
3. **Feedback**: You may receive requests for changes
4. **Approval**: Once approved, your PR will be merged

### Response Times

- **Initial Review**: Within 3-5 business days
- **Follow-up**: Within 1-2 business days
- **Weekend PRs**: Reviewed the following week

### Making Changes

If changes are requested:

```bash
# Make changes on your feature branch
git add .
git commit -m "fix: address review feedback"
git push origin feature/your-feature-name
```

The PR will automatically update.

---

## Security Vulnerabilities

**DO NOT** open public issues for security vulnerabilities.

Instead, email security@example.com with:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if you have one)

We'll respond within 48 hours and work with you on a fix.

---

## Code of Conduct

### Our Pledge

We're committed to providing a welcoming, inclusive environment for all contributors regardless of:
- Experience level
- Gender identity and expression
- Sexual orientation
- Disability
- Personal appearance
- Body size
- Race or ethnicity
- Age
- Religion
- Nationality

### Our Standards

**Positive Behavior:**
- Using welcoming, inclusive language
- Respecting differing viewpoints
- Accepting constructive criticism gracefully
- Focusing on what's best for the community
- Showing empathy toward others

**Unacceptable Behavior:**
- Harassment, trolling, or insulting comments
- Personal or political attacks
- Publishing others' private information
- Any conduct inappropriate in a professional setting


---

## Recognition

Contributors are recognized in:
- CHANGELOG.md for each release
- README.md contributors section
- WordPress.org plugin page credits
- Annual contributor appreciation posts

Significant contributors may be invited to join the core team.

---

## License

By contributing to Saman SEO, you agree that your contributions will be licensed under the same license as the project (see LICENSE).

---

## Questions?

- **GitHub Discussions**: [Ask questions](https://github.com/jhd3197/WP-SEO-Pilot/discussions)

---

**Thank you for helping make WordPress SEO better for everyone!**
