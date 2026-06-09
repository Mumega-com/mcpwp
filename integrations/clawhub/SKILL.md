---
name: mcpwp
description: Manage any WordPress site via 120+ MCP tools — pages, posts, Elementor layouts, SEO audits, media, menus, approvals, site memory, blueprints, and more. Requires the MCPWP plugin on your WordPress site.
version: 1.0.0
metadata:
  openclaw:
    emoji: "🦑"
    primaryEnv: MCPWP_API_KEY
    requires:
      env:
        - MCPWP_API_KEY
        - MCPWP_SITE_URL
    envVars:
      - name: MCPWP_API_KEY
        required: true
        description: "API key generated in WP Admin → MCPWP → Setup. Format: mcpwp_xxxxxxxxxxxxxxxx"
      - name: MCPWP_SITE_URL
        required: true
        description: "Full URL of your WordPress site, e.g. https://example.com (no trailing slash)"
    homepage: https://mcpwp.net
    tags:
      - wordpress
      - cms
      - web
      - elementor
      - seo
      - agency
---

# MCPWP — WordPress MCP Server

Connect OpenClaw to any WordPress site running the [MCPWP plugin](https://mcpwp.net). Gives you 120+ tools for managing content, Elementor layouts, SEO, media, menus, approvals, site memory, and more — far more than any other WordPress MCP server.

## Setup

**Step 1.** Install MCPWP on your WordPress site:

```bash
wp plugin install https://mcpwp.net/download/mcpwp.zip --activate
```

Or download from [mcpwp.net](https://mcpwp.net) and upload via WP Admin → Plugins → Add New.

**Step 2.** Generate an API key: **WP Admin → MCPWP → Setup → Generate API Key**

**Step 3.** Set your environment variables:

```bash
export MCPWP_API_KEY=mcpwp_xxxxxxxxxxxxxxxx
export MCPWP_SITE_URL=https://your-site.com
```

**Step 4.** Register the MCP server:

```bash
openclaw mcp add mcpwp \
  --url "${MCPWP_SITE_URL}/wp-json/mcpwp/v1/mcp" \
  --transport streamable-http \
  --header "X-API-Key: ${MCPWP_API_KEY}"
```

**Step 5.** Verify the connection:

```bash
openclaw mcp probe mcpwp
```

You should see 120+ tools listed.

## First Steps

Always call `wp_onboard` first on a new connection — it returns a complete site briefing:
- Content inventory (pages, posts, media counts)
- Active plugins and integrations
- Elementor layout mode (classic or container/flexbox)
- Recommended first actions

```
wp_onboard()
```

## What You Can Do

### Content
```
wp_list_pages()
wp_create_page(title: "About", status: "draft")
wp_update_post(id: 5, content: "New content here")
wp_search(query: "contact", post_type: "page")
```

### Elementor Layouts
```
wp_get_elementor(id: 5)
wp_set_elementor(id: 5, elementor_data: [...])
wp_edit_widget(page_id: 5, widget_id: "abc123", settings: {title_text: "New heading"})
wp_edit_section(page_id: 5, section_index: 0, settings: {background_color: "#f5f5f5"})
wp_build_page(title: "Services", sections: [{type: "hero"}, {type: "features"}, {type: "cta"}])
```

### SEO
```
wp_seo_audit_site()
wp_get_seo_issues()
wp_validate_seo_readiness(id: 5)
wp_run_seo_autofix_plan()
```

### Media
```
wp_upload_media_from_url(url: "https://example.com/photo.jpg")
wp_list_media(per_page: 20)
```

### Site Memory (persists across sessions)
```
wp_remember(namespace: "brand", key: "tone", value: "professional, no jargon")
wp_recall(namespace: "brand", key: "tone")
wp_list_memories(namespace: "brand")
```

### Site Blueprints
```
wp_list_site_blueprints()
wp_deploy_site_blueprint(id: "saas")
wp_extract_site_blueprint()
```

### Proactive Signals
```
wp_get_signals()
→ returns stale content, broken layouts, missing images, pending updates
```

### Approvals (human-in-the-loop)
```
wp_create_approval_request(tool: "wp_set_elementor", params: {...})
wp_list_approvals()
wp_apply_approval(id: 42)
wp_rollback_approval(id: 42)
```

## Agency Use: Multiple Sites

For agencies managing multiple WordPress sites, MCPWP includes a multi-site proxy. One MCP token gives access to all client sites:

```
wp_list_sites()
wp_get_page(_site: "client1.com", id: 5)
wp_set_elementor(_site: "client2.com", id: 10, elementor_data: [...])
```

See [mcpwp.net](https://mcpwp.net) for agency proxy setup.

## Tool Categories

| Category | What |
|----------|------|
| content | Pages, posts, drafts, bulk ops, search |
| elementor | Get/set data, edit sections/widgets, patch |
| elementor-build | Build pages from section blueprints |
| site | Menus, options, CSS, design refs, guides |
| media | Upload file/URL/base64, screenshot |
| seo | Audit, issues, autofix, search performance |
| memory | Brand rules and decisions across sessions |
| blueprints | Deploy starters, extract current structure |
| approvals | Request → approve → apply → rollback |
| webhooks | Create, test, monitor events |
| admin | API keys, rate limits, settings, updates |
| woocommerce | Products, orders, categories (if installed) |
| learnpress | Courses, lessons, quizzes (if installed) |

## Rules

- Always call `wp_onboard` first on a new site connection.
- Never hardcode the API key — always read from `${MCPWP_API_KEY}`.
- The MCP endpoint pattern is always: `{MCPWP_SITE_URL}/wp-json/mcpwp/v1/mcp`
- If you get a `401 Unauthorized`, the key is wrong or expired — ask the user to regenerate one in WP Admin.
- For Elementor edits, check `elementor_layout_mode` from `wp_onboard` first — classic uses sections, flexbox uses containers.
- Destructive operations (delete page, rollback) require the user to confirm before proceeding.

## Troubleshooting

**401 Unauthorized** — API key invalid or expired. Regenerate in WP Admin → MCPWP → Setup.

**404 on the MCP URL** — MCPWP plugin not active, or permalink structure not set to "Post name". Go to WP Admin → Settings → Permalinks and save.

**Tool not found** — Tool may be disabled (WP Admin → MCPWP → Tools) or your API key scope doesn't include that category.

**Elementor data not saving** — Elementor must be installed and active. Check `wp_detect_plugins()` to confirm.
