---
name: mcpwp
description: Connect Hermes to any WordPress site via MCPWP — 120+ MCP tools for pages, posts, Elementor layouts, SEO, media, menus, approvals, site memory, and blueprints.
version: 1.0.0
platforms: [macos, linux, windows]
metadata:
  hermes:
    tags: [wordpress, mcp, cms, elementor, seo, agency]
    category: integrations
    requires_env:
      - MCPWP_URL
      - MCPWP_API_KEY
    homepage: https://mcpwp.net
---

# MCPWP — WordPress MCP Server

Connect Hermes to any WordPress site running the [MCPWP plugin](https://mcpwp.net).

## Prerequisites

1. MCPWP plugin installed and active on your WordPress site
2. API key generated: **WP Admin → Site Pilot AI → Setup → Generate API Key**
3. Environment variables set:

```bash
export MCPWP_URL="https://your-site.com/wp-json/site-pilot-ai/v1/mcp"
export MCPWP_API_KEY="spai_xxxxxxxxxxxxxxxx"
```

## MCP Server Config

Add to `~/.hermes/config.yaml`:

```yaml
mcp_servers:
  mcpwp:
    url: "${MCPWP_URL}"
    headers:
      X-API-Key: "${MCPWP_API_KEY}"
    timeout: 120
    connect_timeout: 60
    enabled: true
    supports_parallel_tool_calls: false
    tools:
      resources: false
      prompts: false

tools:
  tool_search:
    enabled: on
    threshold_pct: 5
    search_default_limit: 5
    max_search_limit: 20
```

Then reload: `/reload-mcp`

## Procedure

### First connection to any site

Always start with:
```
Use mcp_mcpwp_wp_onboard to get a full site briefing before doing anything else.
```

This returns: content inventory, active plugins, Elementor layout mode, and recommended first actions.

### Managing pages and posts

```
List all pages: mcp_mcpwp_wp_list_pages
Create a page: mcp_mcpwp_wp_create_page with title and status
Update a post: mcp_mcpwp_wp_update_post with id and content
Search content: mcp_mcpwp_wp_search with query and post_type
```

### Building Elementor layouts

Check `elementor_layout_mode` from `wp_onboard` first:
- `classic` → structure is `section > column > widget`
- `container` → structure is `container > widget`

```
Read current layout: mcp_mcpwp_wp_get_elementor with page id
Edit one widget:     mcp_mcpwp_wp_edit_widget with page_id, widget_id, settings
Edit one section:    mcp_mcpwp_wp_edit_section with page_id, section_index, settings
Build a new page:    mcp_mcpwp_wp_build_page with title and sections array
```

### SEO

```
Site audit:     mcp_mcpwp_wp_seo_audit_site
Issues list:    mcp_mcpwp_wp_get_seo_issues
Page readiness: mcp_mcpwp_wp_validate_seo_readiness with page id
Auto-fix plan:  mcp_mcpwp_wp_run_seo_autofix_plan
```

### Site memory (persists across sessions)

```
Save rule:     mcp_mcpwp_wp_remember with namespace, key, value
Recall rule:   mcp_mcpwp_wp_recall with namespace, key
List memory:   mcp_mcpwp_wp_list_memories with namespace
```

Namespaces: `brand`, `design`, `seo`, `decisions`, `custom`

### Site signals (proactive alerts)

```
mcp_mcpwp_wp_get_signals
→ returns stale content, broken Elementor data, missing images, plugin updates
```

### Site blueprints

```
List starters: mcp_mcpwp_wp_list_site_blueprints
Deploy starter: mcp_mcpwp_wp_deploy_site_blueprint with id (law-firm/restaurant/saas/real-estate/portfolio)
Snapshot site:  mcp_mcpwp_wp_extract_site_blueprint
```

### Approvals (human-in-the-loop for writes)

```
Create request: mcp_mcpwp_wp_create_approval_request with tool and params
List pending:   mcp_mcpwp_wp_list_approvals
Apply:          mcp_mcpwp_wp_apply_approval with id
Rollback:       mcp_mcpwp_wp_rollback_approval with id
```

## Rules

- Always call `mcp_mcpwp_wp_onboard` first on a new site.
- Never hardcode credentials — always use `${MCPWP_URL}` and `${MCPWP_API_KEY}`.
- For Elementor edits, check `elementor_layout_mode` from onboard before writing.
- Confirm before running destructive tools (delete page, delete post, rollback).
- If `401 Unauthorized`, the API key is wrong or expired — ask user to regenerate.
- If `404` on MCP URL, tell user to go to WP Admin → Settings → Permalinks and save.

## Tool Categories

| Prefix | Category |
|--------|----------|
| `wp_list_pages`, `wp_create_page`, `wp_update_page` | content |
| `wp_get_elementor`, `wp_set_elementor`, `wp_edit_widget` | elementor |
| `wp_build_page` | elementor-build |
| `wp_seo_audit_site`, `wp_get_seo_issues` | seo |
| `wp_upload_media_from_url`, `wp_list_media` | media |
| `wp_remember`, `wp_recall`, `wp_list_memories` | memory |
| `wp_list_site_blueprints`, `wp_deploy_site_blueprint` | blueprints |
| `wp_create_approval_request`, `wp_apply_approval` | approvals |
| `wp_get_signals` | site |
| `wp_onboard`, `wp_site_info` | site |
