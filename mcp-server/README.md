# MCPWP

[![npm version](https://img.shields.io/npm/v/site-pilot-ai.svg)](https://www.npmjs.com/package/site-pilot-ai)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)

**MCP Server for WordPress** — dynamic, scoped-access tools for posts, pages, Elementor, WooCommerce, LearnPress, SEO, forms & more. Works with Claude Desktop, Cursor, Windsurf and any MCP client.

A thin stdio-to-HTTP proxy that forwards all MCP requests to your WordPress site's built-in MCP endpoint. Tools are always in sync with the plugin — zero local definitions, zero maintenance.

## How It Works

```
MCP Client (stdio) → site-pilot-ai (proxy) → WordPress Plugin (JSON-RPC over HTTP)
```

The WordPress plugin exposes a complete MCP endpoint at `/wp-json/site-pilot-ai/v1/mcp`. This npm package connects to it and proxies `tools/list`, `tools/call`, `resources/list`, and `resources/read` — so every tool the plugin provides is automatically available to your AI client.

- **Dynamic tool discovery** — content, Elementor, WooCommerce, LearnPress, SEO, forms, media, theme builder, and more
- **Zero dependencies** — single-file bundle, runs on Node 18+
- **Always in sync** — update the plugin, tools appear instantly
- **AI-native** — built-in onboarding, guides, error hints, and widget help teach AI models how to use your site

## Quick Start

### 1. Install WordPress Plugin

Install **MCPWP** on your WordPress site:
1. Download from [GitHub releases](https://github.com/Mumega-com/mcp-for-wp/releases) or your Freemius account
2. Upload to WordPress: **WP Admin > Plugins > Add New > Upload Plugin**
3. Activate and copy your API key from **MCPWP** (top-level admin menu)

### 2. Run Setup Wizard

```bash
npx -y site-pilot-ai --setup
```

This will:
- Prompt for your WordPress URL and API key
- Test the connection
- Save configuration to `~/.mumega-mcp/config.json`
- Show Claude Desktop config snippet

### 3. Configure Your MCP Client

**Claude Desktop** — add to `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "sitepilotai-mysite": {
      "command": "npx",
      "args": ["-y", "site-pilot-ai"],
      "env": {
        "WP_URL": "https://your-site.com",
        "WP_API_KEY": "spai_your_key"
      }
    }
  }
}
```

**Claude Code** — add to `.mcp.json` in your project:

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": ["-y", "site-pilot-ai"],
      "env": {
        "WP_URL": "https://your-site.com",
        "WP_API_KEY": "spai_your_key"
      }
    }
  }
}
```

**Streamable HTTP** — connect directly without the npm proxy:

```json
{
  "mcpServers": {
    "wordpress": {
      "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
      "headers": {
        "X-API-Key": "spai_your_key"
      }
    }
  }
}
```

For multiple sites, add separate entries with unique names:

```json
{
  "mcpServers": {
    "sitepilotai-production": {
      "command": "npx",
      "args": ["-y", "site-pilot-ai"],
      "env": { "WP_URL": "https://example.com", "WP_API_KEY": "spai_..." }
    },
    "sitepilotai-staging": {
      "command": "npx",
      "args": ["-y", "site-pilot-ai"],
      "env": { "WP_URL": "https://staging.example.com", "WP_API_KEY": "spai_..." }
    }
  }
}
```

**Cursor / Windsurf** — same format in their MCP settings.

### 4. Restart Your Client

Tools appear automatically. Try: *"Show me my site info"* or *"Onboard me to this site"*

## Available Tools

All tools come from the WordPress plugin. Tool availability varies by site, installed plugins, enabled integrations, and API-key scope. Update the plugin to get new tools — no npm update needed.

### Core

**Content** — `wp_list_posts`, `wp_create_post`, `wp_update_post`, `wp_delete_post`, `wp_list_pages`, `wp_create_page`, `wp_update_page`, `wp_delete_page`, `wp_search`, `wp_list_drafts`, `wp_delete_all_drafts`

**Media** — `wp_upload_media`, `wp_upload_media_from_url`, `wp_upload_media_b64`, `wp_list_media`, `wp_delete_media`

**Elementor** — `wp_get_elementor`, `wp_set_elementor`, `wp_preview_elementor`, `wp_elementor_widget_help`, `wp_regenerate_elementor_css`

**Site & Settings** — `wp_site_info`, `wp_onboard`, `wp_introspect`, `wp_analytics`, `wp_detect_plugins`, `wp_get_options`, `wp_update_options`

**AI Education** — `wp_onboard` (full site overview), `wp_get_guide` (topic guides), `wp_get_workflow` (workflow templates), `wp_elementor_widget_help` (widget help)

**Taxonomies** — `wp_list_categories`, `wp_list_tags`, `wp_create_term`, `wp_update_term`, `wp_delete_term`

**Menus** — `wp_list_menus`, `wp_list_menu_items`, `wp_add_menu_item`, `wp_update_menu_item`, `wp_delete_menu_item`

### Pro

**SEO** (Yoast / RankMath / AIOSEO / SEOPress) — `wp_get_seo`, `wp_set_seo`, `wp_analyze_seo`, `wp_bulk_seo`, `wp_seo_status`

**Forms** (CF7 / WPForms / Gravity Forms) — `wp_list_forms`, `wp_get_form`, `wp_get_form_entries`, `wp_forms_status`

**WooCommerce** — `wc_status`, `wc_list_products`, `wc_create_product`, `wc_update_product`, `wc_delete_product`, `wc_list_orders`, `wc_get_order`, `wc_update_order`, `wc_list_customers`, `wc_analytics`, `wc_list_product_categories`, `wc_create_product_category`

**LearnPress LMS** — `wp_list_courses`, `wp_create_course`, `wp_update_course`, `wp_get_curriculum`, `wp_set_curriculum`, `wp_list_lessons`, `wp_create_lesson`, `wp_list_quizzes`, `wp_create_quiz`, `wp_list_course_categories`, `wp_lms_stats`

**Elementor Pro** — `wp_list_elementor_templates`, `wp_create_elementor_template`, `wp_apply_elementor_template`, `wp_create_landing_page`, `wp_clone_elementor_page`, `wp_get_elementor_globals`, `wp_set_elementor_globals`, `wp_build_page`

**Theme Builder** — `wp_theme_builder_status`, `wp_list_theme_templates`, `wp_create_theme_template`, `wp_set_template_conditions`, `wp_assign_template`

**Events** (ThimPress) — `wp_list_events`, `wp_create_event`, `wp_update_event`

**AI Tools** — `wp_search_stock_photos`, `wp_download_stock_photo`, `wp_generate_image`, `wp_generate_featured_image`, `wp_generate_alt_text`, `wp_describe_image`, `wp_generate_excerpt`, `wp_text_to_speech`

**...and more.** Run `npx site-pilot-ai --test` to see the tool set for your site.

## Configuration

### Environment Variables

```bash
WP_URL=https://your-site.com       # WordPress site URL
WP_API_KEY=spai_...                 # MCPWP API key
WP_SITE_NAME=default                # Optional, for multi-site configs
WP_CONFIG_PATH=~/custom/config.json # Optional, custom config path
```

### Config File

Location: `~/.mumega-mcp/config.json`

```json
{
  "sites": {
    "default": {
      "url": "https://your-site.com",
      "apiKey": "spai_...",
      "name": "My Site"
    }
  },
  "defaultSite": "default"
}
```

Environment variables take priority over the config file.

## CLI Commands

```bash
npx site-pilot-ai              # Start MCP server (stdio transport)
npx site-pilot-ai --setup      # Interactive setup wizard
npx site-pilot-ai --test       # Test WordPress connection
npx site-pilot-ai --version    # Show version
npx site-pilot-ai --help       # Show help
```

## Troubleshooting

### Connection Failed

```bash
npx site-pilot-ai --test
```

Verify:
1. WordPress site is accessible
2. MCPWP plugin is activated
3. API key is correct (regenerate in WP Admin if needed)
4. REST API is not blocked by firewall or security plugin

### No Tools Appearing

1. Restart your MCP client
2. Check config: `cat ~/.mumega-mcp/config.json`
3. Test connection: `WP_URL=... WP_API_KEY=... npx site-pilot-ai --test`
4. Check client logs for MCP errors

### Plugin Requirements

**Required:**
- WordPress 5.9+
- MCPWP plugin (v2.8.31+)

**Optional (enables more tools):**
- **Elementor** / Elementor Pro — page builder & theme builder tools
- **WooCommerce** — product, order, customer tools
- **LearnPress** — course, lesson, quiz tools
- **Yoast SEO** / RankMath / AIOSEO / SEOPress — SEO tools
- **Contact Form 7** / WPForms / Gravity Forms — form tools
- **ThimPress Events** — event management tools

## Development

```bash
git clone https://github.com/Mumega-com/mcp-for-wp.git
cd mcp-for-wp/mcp-server
bun install
bun run build       # Single-file bundle to dist/index.js
node dist/index.js --test
```

## License

MIT © Mumega

---

**Documentation:** https://mcpwp.net
**Issues:** https://github.com/Mumega-com/mcp-for-wp/issues
**WordPress Plugin:** https://github.com/Mumega-com/mcp-for-wp/releases
