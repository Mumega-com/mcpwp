# MCPWP for WordPress — Connect Claude, Gemini, GPT to Your Site in 2 Minutes

WordPress has 43% of the web. AI agents are the future of site management. But until now, connecting the two required custom code, fragile REST API wrappers, or expensive SaaS tools.

**MCPWP** changes that. One plugin, up to 250+ MCP tools depending on installed integrations, every major MCP client, and paid plans with a trial for production teams.

## What is it?

MCPWP is a WordPress plugin that turns your site into an MCP (Model Context Protocol) server. Once installed, any MCP-compatible AI assistant — Claude, Gemini, GPT, Cursor, Windsurf, or your local Ollama — can manage your entire site through natural language.

```
You: "Build a landing page with a hero, 3 feature cards, and a CTA"
AI:  → creates a full Elementor page with styled sections, flex grid, shadows, hover effects
```

No code. No Elementor JSON. No WordPress admin.

## The numbers

- **~119 free MCP tools** in the core plugin; **250+ total** across free + Pro depending on active integrations
- **14 page blueprints** (hero, features, pricing, FAQ, testimonials, stats, and more)
- **Elementor 4 support** with validation that auto-fixes your AI's mistakes
- **Role-scoped API keys** — give your designer bot 82 tools, your content writer 40
- **Paid plans and trial access** — managed through Freemius, with live tools scoped by plan, integrations, and API-key role

## How it compares

The closest competitor is Royal MCP with 37 tools. MCPWP exposes 250+ tools across free and Pro depending on active integrations. Elementor's Angie is in beta with credit limits. We're production-ready for agencies and operators who need broader WordPress coverage.

| Feature | MCPWP | Royal MCP | Elementor Angie |
|---------|-------|-----------|-----------------|
| MCP tools | ~119 free / 250+ with Pro integrations | 37 | Unknown |
| Elementor support | Full (build + edit + templates) | No | Yes (limited) |
| WooCommerce | 21 tools | No | No |
| Price | Paid plans + trial | Free | Free (beta credits) |
| Role-scoped keys | Yes (5 roles) | No | No |
| Page blueprints | 14 types | No | No |

## Install in 2 minutes

**Step 1: Install the plugin**
```bash
wp plugin install https://mumega.com/mcp-updates/mumega-mcp-latest.zip --activate
```

**Step 2: Generate an API key**
WP Admin → MCPWP → Setup → Generate API Key

**Step 3: Connect your AI**
```json
{
  "mcpServers": {
    "mumcp": {
      "url": "https://your-site.com/wp-json/site-pilot-ai/v1/mcp",
      "headers": {"X-API-Key": "spai_your_key"}
    }
  }
}
```

That's it. Your AI can now manage your entire WordPress site.

## What can it do?

### Build pages from blueprints
```
wp_build_page(title: "Services", sections: [
  {type: "hero", heading: "Our Services", button_text: "Get Started"},
  {type: "features", columns: 3, items: [
    {icon: "fas fa-rocket", title: "Fast", desc: "Lightning speed"},
    {icon: "fas fa-shield-alt", title: "Secure", desc: "Bank-grade security"},
    {icon: "fas fa-heart", title: "Reliable", desc: "99.9% uptime"}
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
The available tools are documented by the live MCP endpoint. Your AI discovers them automatically via `wp_introspect()`.

## For Claude Code users

Install our Claude Code plugin for guided setup and WordPress knowledge:
```bash
claude plugin marketplace add https://github.com/Mumega-com/mumcp-claude-plugin.git
claude plugin install mumcp@mumcp
```

## Why we built it

We're Mumega — an AI agency building tools for the WordPress ecosystem. MCPWP packages the operational layer agencies need: site context, API keys, role scopes, WordPress actions, Elementor workflows, and integrations behind one MCP endpoint.

250+ tools depending on installed integrations. Every major MCP client. Your WordPress site, controlled through a paid product with trial access.

**Links:**
- Website: https://mcpwp.net
- GitHub (plugin): https://github.com/Mumega-com/mcp-for-wp
- GitHub (Claude Code plugin): https://github.com/Mumega-com/mumcp-claude-plugin
- Download: https://mumega.com/mcp-updates/mumega-mcp-latest.zip

---
*Built by Mumega — https://mumega.com*
