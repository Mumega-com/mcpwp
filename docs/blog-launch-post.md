# MCPWP for WordPress - Connect Claude, Gemini, GPT to Your Site

WordPress already powers a large part of the web. MCPWP connects that surface area to MCP clients without forcing a fixed tool list or a brittle integration layer.

**MCPWP** turns a WordPress site into a dynamic MCP server. Tool availability depends on installed plugins, enabled integrations, and the scope attached to the API key.

## What is it?

MCPWP is a WordPress plugin that exposes your site through MCP (Model Context Protocol). Once installed, any MCP-compatible AI assistant - Claude, Gemini, GPT, Cursor, Windsurf, or a local client - can inspect and manage the site through natural language.

```
You: "Build a landing page with a hero, 3 feature cards, and a CTA"
AI:  → creates a full Elementor page with styled sections and scoped tool access
```

No custom code. No manual Elementor JSON. No separate agent bridge.

## What it gives you

- Dynamic MCP tools that reflect the active site and license state
- Role-scoped API keys for controlled access
- Reusable page blueprints for common WordPress page types
- Elementor validation that catches and fixes common AI mistakes
- Live tool discovery through the site endpoint

## How it compares

MCPWP is designed for teams that want site-aware MCP tooling instead of a fixed, one-size-fits-all surface.

| Feature | MCPWP | Typical static adapter |
|---------|-------|------------------------|
| MCP tools | Dynamic, site-aware discovery | Fixed set |
| Elementor support | Full build, edit, and template workflows | Limited or none |
| WooCommerce | Available when installed and enabled | Often unsupported |
| Access control | Role-scoped API keys | Usually site-wide |
| Tool discovery | Live from the site endpoint | Hard-coded |
| Update path | Current release manifest and ZIP | Varies |

## Install

**Step 1: Install the plugin**
```bash
wp plugin install https://mumega.com/mcp-updates/mcpwp-latest.zip --activate
```

**Step 2: Generate an API key**
WP Admin → MCPWP → Setup → Generate API Key

**Step 3: Connect your AI**
```json
{
  "mcpServers": {
    "mcpwp": {
      "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
      "headers": {"X-API-Key": "spai_your_key"}
    }
  }
}
```

## What can it do?

### Build pages from blueprints
```
wp_build_page(title: "Services", sections: [
  {type: "hero", heading: "Our Services", button_text: "Get Started"},
  {type: "features", columns: 3, items: [
    {icon: "fas fa-rocket", title: "Fast", desc: "Speed matters"},
    {icon: "fas fa-shield-alt", title: "Secure", desc: "Safer workflows"},
    {icon: "fas fa-heart", title: "Reliable", desc: "Consistent output"}
  ]},
  {type: "cta", heading: "Ready?", button_text: "Contact Us"}
])
```

### Edit one widget without touching the rest
```
wp_edit_widget(page_id: 42, widget_id: "abc123", settings: {title_text: "New Title"})
```

### Manage WooCommerce
```
wc_create_product(name: "T-Shirt", regular_price: "29.99", type: "simple")
```

### SEO, media, menus, taxonomies, courses, and more
The live MCP endpoint documents the exact tools available on the connected site.

## Why we built it

MCPWP packages the operational layer agencies and site operators need: site context, API keys, role scopes, WordPress actions, Elementor workflows, and integrations behind one MCP endpoint.

**Links:**
- Website: https://mcpwp.net
- GitHub (plugin): https://github.com/Mumega-com/mcp-for-wp
- Download: https://mumega.com/mcp-updates/mcpwp-latest.zip

---
*Built by Mumega - https://mumega.com*
