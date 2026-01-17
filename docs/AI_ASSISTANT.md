# AI Assistant Guide

Complete guide to using AI-powered SEO features in Saman SEO.

---

## Table of Contents

- [Overview](#overview)
- [Setup & Configuration](#setup--configuration)
- [Using AI Features](#using-ai-features)
- [Customizing AI Prompts](#customizing-ai-prompts)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)
- [Cost Management](#cost-management)

---

## Overview

Saman SEO integrates with OpenAI's GPT models to provide intelligent SEO suggestions:

- **AI-Generated Titles**: Create compelling, SEO-optimized titles
- **AI-Generated Descriptions**: Write engaging meta descriptions
- **Batch Processing**: Generate metadata for multiple posts at once
- **Customizable Prompts**: Tailor AI behavior to your needs
- **Model Selection**: Choose the best model for your use case

**Location:** Navigate to **Saman SEO → AI Assistant**

**File:** `includes/class-wpseopilot-service-ai-assistant.php`

---

## Setup & Configuration

### Step 1: Get an OpenAI API Key

1. Visit [OpenAI Platform](https://platform.openai.com/)
2. Sign up or log in to your account
3. Navigate to **API Keys**
4. Click **Create new secret key**
5. Copy your API key (starts with `sk-`)

**Important:** Never share your API key publicly.

---

### Step 2: Configure API Key in WordPress

1. Navigate to **Saman SEO → General Settings**
2. Find the **OpenAI Settings** section
3. Paste your API key in **OpenAI API Key** field
4. Click **Save Changes**

**Option:** `wpseopilot_openai_api_key`

```php
// Programmatically set API key
update_option( 'wpseopilot_openai_api_key', 'sk-your-api-key-here' );
```

---

### Step 3: Choose AI Model

**Option:** `wpseopilot_ai_model`

**Available Models:**

| Model | Speed | Cost | Quality | Recommended For |
|-------|-------|------|---------|-----------------|
| `gpt-4o` | Medium | $$$ | Excellent | High-quality, nuanced content |
| `gpt-4o-mini` | Fast | $ | Great | Most use cases (Default) |
| `gpt-3.5-turbo` | Very Fast | $ | Good | Budget-conscious, high-volume |

**Default:** `gpt-4o-mini`

```php
// Set AI model
update_option( 'wpseopilot_ai_model', 'gpt-4o-mini' );
```

**Model Selection Guidelines:**

- **gpt-4o**: Best for complex topics, technical content, or when quality is paramount
- **gpt-4o-mini**: Best balance of speed, cost, and quality (recommended default)
- **gpt-3.5-turbo**: Budget option for simple content or bulk generation

---

## Using AI Features

### Generate Title for Single Post

1. Edit any post or page
2. Scroll to the **Saman SEO** meta box
3. Click **Generate AI Title**
4. Review the suggestion
5. Click **Use This Title** or regenerate

**AJAX Handler:** `wp_ajax_wpseopilot_generate_ai`

---

### Generate Description for Single Post

1. Edit any post or page
2. In the **Saman SEO** meta box
3. Click **Generate AI Description**
4. Review the suggestion
5. Click **Use This Description** or regenerate

---

### Batch Generation

Navigate to **Saman SEO → AI Assistant**:

#### Step 1: Select Posts

- **Filter by Post Type**: Posts, Pages, Products, etc.
- **Filter by Status**: Published, Draft, Pending
- **Filter by Date**: Last 7 days, Last 30 days, All time
- **Manually Select**: Check individual posts

---

#### Step 2: Choose Generation Options

- **Generate Titles Only**
- **Generate Descriptions Only**
- **Generate Both**

---

#### Step 3: Process

1. Click **Generate AI Metadata**
2. Wait for processing (progress bar displays)
3. Review generated content
4. Apply or discard suggestions

---

### AI Suggestions in Post Editor

When editing a post, AI suggestions appear automatically in the meta box:

```
Suggested Title: "10 Proven Strategies to Boost Your WordPress SEO in 2025"
Suggested Description: "Discover expert-tested WordPress SEO techniques that deliver results. Learn how to optimize your site, improve rankings, and drive more organic traffic."
```

---

## Customizing AI Prompts

Saman SEO allows complete customization of AI prompts to match your brand voice and SEO strategy.

### System Prompt

**Option:** `wpseopilot_ai_prompt_system`

The system prompt establishes the AI's role and behavior.

**Default:**

```
You are an expert SEO copywriter specialized in creating compelling, search-engine-optimized content. Your goal is to write titles and meta descriptions that are:
- Engaging and click-worthy
- Optimized for search engines
- Accurate to the content
- Within character limits (titles: 50-60 chars, descriptions: 150-160 chars)
```

**Customize:**

```php
$system_prompt = "You are a professional SEO expert for a tech blog. Write in a casual, friendly tone that appeals to developers and tech enthusiasts. Focus on clear, actionable language.";

update_option( 'wpseopilot_ai_prompt_system', $system_prompt );
```

---

### Title Generation Prompt

**Option:** `wpseopilot_ai_prompt_title`

**Default:**

```
Write an SEO-optimized title for a blog post with the following details:

Title: {{post_title}}
Content: {{post_content}}
Category: {{category}}
Tags: {{tags}}

Requirements:
- 50-60 characters maximum
- Include primary keyword naturally
- Compelling and click-worthy
- Accurate to content

Return only the title, nothing else.
```

**Available Variables:**
- `{{post_title}}` - Original post title
- `{{post_content}}` - Post content
- `{{post_excerpt}}` - Post excerpt
- `{{category}}` - Primary category
- `{{tags}}` - Comma-separated tags
- `{{site_title}}` - Site name
- `{{author}}` - Author name

**Example Customization:**

```php
$title_prompt = "Create a compelling SEO title for this {{category}} article:

Title: {{post_title}}
Excerpt: {{post_excerpt}}

Make it action-oriented, include numbers if relevant, and keep it under 60 characters.

Title:";

update_option( 'wpseopilot_ai_prompt_title', $title_prompt );
```

---

### Description Generation Prompt

**Option:** `wpseopilot_ai_prompt_description`

**Default:**

```
Write an SEO-optimized meta description for a blog post:

Title: {{post_title}}
Content: {{post_content}}

Requirements:
- 150-160 characters maximum
- Include primary keyword
- Compelling and informative
- Include a call-to-action when appropriate

Return only the description, nothing else.
```

**Example Customization:**

```php
$description_prompt = "Write a meta description for this e-commerce product:

Product: {{post_title}}
Details: {{post_content}}

Style: Professional, persuasive
Length: 150-160 characters
Include: Key benefit, call-to-action, urgency

Description:";

update_option( 'wpseopilot_ai_prompt_description', $description_prompt );
```

---

## Advanced Prompt Strategies

### Strategy 1: Industry-Specific

```php
// For medical/health sites
$system_prompt = "You are a medical content specialist. Write evidence-based, YMYL-compliant SEO metadata. Avoid sensationalism. Use professional, trustworthy language.";

update_option( 'wpseopilot_ai_prompt_system', $system_prompt );
```

---

### Strategy 2: Brand Voice

```php
// For fun, casual brands
$system_prompt = "You're a creative copywriter for a playful brand. Write SEO metadata that's witty, engaging, and slightly cheeky while remaining professional and accurate.";

update_option( 'wpseopilot_ai_prompt_system', $system_prompt );
```

---

### Strategy 3: Local SEO

```php
$title_prompt = "Write an SEO title for this local business page:

Business: {{post_title}}
Content: {{post_excerpt}}
Location: New York, NY

Include location naturally. Keep it under 60 characters.

Title:";

update_option( 'wpseopilot_ai_prompt_title', $title_prompt );
```

---

### Strategy 4: E-commerce

```php
$description_prompt = "Write a product meta description:

Product: {{post_title}}
Details: {{post_excerpt}}

Include:
- Primary benefit
- Price point (if mentioned)
- Call-to-action (e.g., 'Buy now', 'Shop today')
- Urgency or scarcity (if applicable)

Length: 150-160 characters

Description:";

update_option( 'wpseopilot_ai_prompt_description', $description_prompt );
```

---

## Best Practices

### 1. Review AI Suggestions

Always review and edit AI-generated content:
- Ensure accuracy
- Verify keyword usage
- Check brand voice alignment
- Confirm character limits

---

### 2. Use Batch Processing Strategically

Best for:
- New content without metadata
- Seasonal updates (e.g., updating year in titles)
- Content audits

Avoid for:
- Highly specialized content
- Legal/medical pages
- Content requiring exact phrasing

---

### 3. Provide Context in Prompts

Better results come from detailed prompts:

**Bad:**
```
Write a title.
```

**Good:**
```
Write an SEO title for a WordPress tutorial aimed at beginners. Include the main benefit and keep it under 60 characters.
```

---

### 4. Iterate and Refine

If results aren't satisfactory:
1. Adjust system prompt
2. Add more context to prompts
3. Try a different model
4. Regenerate multiple times

---

### 5. Test Different Models

Run A/B tests:
- Generate titles with `gpt-4o` for 10 posts
- Generate titles with `gpt-4o-mini` for 10 posts
- Compare CTR after 30 days

---

### 6. Combine AI with Human Expertise

Best workflow:
1. AI generates initial draft
2. Human reviews and edits
3. Human adds brand-specific touches
4. Final approval before publishing

---

## Cost Management

### Understanding OpenAI Pricing

Pricing is based on tokens (roughly 4 characters = 1 token).

**Approximate Costs (as of 2025):**

| Model | Input (per 1M tokens) | Output (per 1M tokens) |
|-------|----------------------|------------------------|
| gpt-4o | $2.50 | $10.00 |
| gpt-4o-mini | $0.15 | $0.60 |
| gpt-3.5-turbo | $0.50 | $1.50 |

**Average Cost Per Generation:**

- Title: ~$0.001 - $0.01 (depending on model and post length)
- Description: ~$0.001 - $0.01

---

### Cost Optimization Tips

#### 1. Use Smaller Models for Simple Content

```php
// For straightforward blog posts
update_option( 'wpseopilot_ai_model', 'gpt-4o-mini' );
```

---

#### 2. Limit Content Sent to AI

Only send relevant excerpts, not entire 5,000-word posts.

**Customize Prompt:**

```php
$title_prompt = "Write an SEO title:

Title: {{post_title}}
Excerpt: {{post_excerpt}}
Category: {{category}}

(Note: Removed full content to save tokens)

Title:";
```

---

#### 3. Set Usage Limits in OpenAI Dashboard

1. Go to [OpenAI Platform](https://platform.openai.com/)
2. Navigate to **Billing → Usage limits**
3. Set monthly spending cap
4. Enable email notifications

---

#### 4. Batch Wisely

Instead of regenerating for all 1,000 posts:
- Start with posts missing metadata (200 posts)
- Focus on high-traffic pages first
- Generate only what's needed

---

### Monitoring Usage

Check usage in OpenAI Dashboard:
- **Billing → Usage**: View daily/monthly usage
- **Set up alerts**: Email when approaching limits

---

## Troubleshooting

### Error: "Invalid API Key"

**Cause:** API key is incorrect or expired

**Solution:**
1. Verify API key in [OpenAI Platform](https://platform.openai.com/api-keys)
2. Generate new key if needed
3. Update in **Saman SEO → General Settings**

---

### Error: "Rate Limit Exceeded"

**Cause:** Too many requests in short time

**Solution:**
1. Wait 60 seconds and retry
2. Use batch processing with delays
3. Upgrade OpenAI plan for higher limits

---

### Error: "Insufficient Quota"

**Cause:** OpenAI account has no remaining credits

**Solution:**
1. Add payment method in [OpenAI Billing](https://platform.openai.com/account/billing)
2. Purchase credits or set up auto-recharge

---

### Poor Quality Results

**Causes:**
- Generic prompts
- Wrong model for content type
- Insufficient context

**Solutions:**
1. Refine system and generation prompts
2. Try a more capable model (upgrade to gpt-4o)
3. Provide more context in prompts
4. Include examples in system prompt

---

### Timeout Errors

**Cause:** Large posts causing slow API responses

**Solution:**
1. Reduce content sent to API (use excerpts)
2. Increase PHP max execution time
3. Process smaller batches

---

## Reset AI Settings

Navigate to **Saman SEO → AI Assistant** and click **Reset to Defaults**.

Or programmatically:

```php
// Reset all AI settings to defaults
delete_option( 'wpseopilot_ai_model' );
delete_option( 'wpseopilot_ai_prompt_system' );
delete_option( 'wpseopilot_ai_prompt_title' );
delete_option( 'wpseopilot_ai_prompt_description' );
```

---

## Security Best Practices

### 1. Protect Your API Key

- Never commit API keys to version control
- Use environment variables:

```php
// wp-config.php
define( 'WPSEOPILOT_OPENAI_KEY', getenv( 'OPENAI_API_KEY' ) );

// In plugin
$api_key = defined( 'WPSEOPILOT_OPENAI_KEY' ) ? WPSEOPILOT_OPENAI_KEY : get_option( 'wpseopilot_openai_api_key' );
```

---

### 2. Restrict User Access

Only allow trusted users to access AI features:

```php
add_filter( 'wpseopilot_feature_toggle', function( $enabled, $feature ) {
    if ( $feature === 'ai_assistant' && ! current_user_can( 'manage_options' ) ) {
        return false;
    }

    return $enabled;
}, 10, 2 );
```

---

### 3. Monitor API Usage

Regularly check OpenAI dashboard for unexpected usage spikes.

---

## Related Documentation

- **[Getting Started](GETTING_STARTED.md)** - Basic plugin setup
- **[Developer Guide](DEVELOPER_GUIDE.md)** - Extending AI features
- **[Filter Reference](FILTERS.md)** - AI-related filters

---

## External Resources

- **[OpenAI Platform](https://platform.openai.com/)**
- **[OpenAI API Documentation](https://platform.openai.com/docs/)**
- **[OpenAI Pricing](https://openai.com/pricing)**

---

**For more help, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
