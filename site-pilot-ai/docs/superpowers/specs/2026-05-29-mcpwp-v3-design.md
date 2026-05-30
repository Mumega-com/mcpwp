# MCPWP v3 — Architecture Design
**Date:** 2026-05-29  
**Status:** Draft — updated after full session  
**Owner:** Product (Hadi / Mumega)  
**Target:** mcpwp.net v3 — clean break from v2

---

## Context

MCPWP v2 (`site-pilot-ai`, 2.8.31) is a mature WordPress MCP plugin with ~200 tools, approval gates, content graph, SEO intelligence, and WooCommerce support. It's used internally on 5 sites. v3 is a clean break — new slug, new architecture, new capabilities.

**The three architectural problems v3 solves:**
1. **Fat MCP** — 200+ tool schemas sent to the AI at connect time, burning thousands of tokens
2. **No extensibility** — only tools Mumega ships are available; no way for site owners or other plugins to add tools
3. **External data is blind** — the agent can only see what's in WordPress; can't pull GSC, GA, external APIs, or navigate the live site

---

## What Changes in v3

| Concern | v2 | v3 |
|---------|----|----|
| Plugin slug | `site-pilot-ai` | `mcpwp` |
| REST namespace | `site-pilot-ai/v1` | `mcpwp/v3` |
| Option prefix | `spai_` | `mcpwp_` |
| Class prefix | `Spai_` | `MCPWP_` |
| MCP transport | Node.js stdio proxy → PHP REST | PHP-native HTTP + stdio proxy kept |
| Tool list at connect | ~200 tools | 2 meta-tools |
| Tool loading | Eager (all upfront) | Lazy (by category, on demand) |
| Custom tools | Not possible | Admin UI + plugin hooks |
| External data | None | API key integrations (GSC, GA, custom) |
| Browser navigation | Screenshot only | Full browser agent (Pro) |
| Messaging channel | None | Telegram alerts + commands (Pro) |
| Node.js proxy | `site-pilot-ai` on npm | `mcpwp` on npm |

**Migration:** Internal only (5 sites). Reinstall fresh. No compatibility shim.

---

## Three Core Bets

### Bet 1 — Thin MCP (Dynamic Tool Loading)

**Problem:** Every session sends all 200+ tool schemas upfront. Wasted tokens every time.

**Solution:** At connect time the agent sees 2 tools only:

```
mcpwp.list_categories  → ["content", "seo", "elementor", "woocommerce", "custom", ...]
mcpwp.use_category     → loads full schemas for one category, returns them as tool result
```

Agent calls `use_category("seo")` when doing SEO work. Never pays for Elementor or WooCommerce schemas in that session. **Estimated savings: 80% token reduction at connect.**

**Implementation:** PHP-side. `tools/list` returns only 2 meta-tools. `use_category` returns the full schema array as its response content — the agent reads and uses them within the session. Works within MCP spec, no proxy tricks.

**Opt-out:** `mcpwp_tool_loading: eager` option for clients that want everything upfront.

---

### Bet 2 — PHP-Native HTTP Transport

**Problem:** Node.js proxy exists only to bridge stdio → HTTP. HTTP clients don't need it.

**Solution:** PHP MCP endpoint at `/wp-json/mcpwp/v3/mcp`:
- `POST` — handles `initialize`, `tools/list`, `tools/call`
- `GET` — SSE stream for server-initiated messages
- Auth: `X-API-Key` header (same as REST API)

**Node.js proxy:** Keep it, rename npm package to `mcpwp`. Pure pass-through, no logic change. Config path moves to `~/.mcpwp/config.json`. Claude Desktop uses the proxy; Claude Code and Cursor connect directly.

---

### Bet 3 — Custom Tools Platform

**Three layers:**

#### Layer 1 — Admin UI (no code required)
**MCPWP → Custom Tools** admin screen. Site owner defines:
- Tool name, description, category
- Input schema (guided form)
- Handler: REST endpoint + method + parameter mapping
- Whether it requires human approval

Stored in `mcpwp_custom_tools` option. Appears in tool list immediately.

#### Layer 2 — Plugin Hook (developer)
```php
add_filter( 'mcpwp_register_tools', function( $registry ) {
    $registry->add( 'my_plugin.action', [
        'description'  => 'Does something in My Plugin',
        'category'     => 'my_plugin',
        'input_schema' => [ 'type' => 'object', 'properties' => [] ],
        'handler'      => [ My_Plugin::class, 'handle' ],
        'tier'         => 'free',
    ] );
    return $registry;
} );
```

Any WordPress plugin can become AI-accessible without touching MCPWP code.

#### Layer 3 — WordPress Abilities Bridge (automatic)
WordPress 6.x ships the Abilities API (`WordPress/mcp-adapter`). Any registered WordPress Ability auto-bridges into MCPWP's tool list. No config needed.

---

## v3 Pro Features (beyond current v2 Pro)

### External Data Ingestion
Site owner provides API keys for external sources:
- **Google Search Console** — search performance already partially built in v2; formalize as a tool category
- **Google Analytics** — traffic data, conversion data
- **Custom REST APIs** — any endpoint with an API key becomes a readable resource
- **WooCommerce analytics** — revenue, top products, abandoned carts (pulling from WC directly)

Pattern: each integration is a `MCPWP_Data_Source` that registers read-only MCP resources (not tools — they're data, not actions).

### Browser Navigation Agent
Playwright/Puppeteer via the existing screenshot worker integration. Tools:
- `mcpwp.navigate(url)` — load a page, return rendered HTML + screenshot
- `mcpwp.click(selector)` — click an element
- `mcpwp.check_links(url)` — crawl and find 404s
- `mcpwp.checkout_test()` — run through WooCommerce checkout flow

Requires screenshot worker endpoint (already in Pro integrations). Store owners can test their own UX.

### Telegram Channel (and Discord)
Store owner connects their Telegram bot token. MCPWP:
- **Pushes alerts** — low stock, failed orders, SEO drops, approval requests
- **Accepts commands** — owner replies "approve" or "fix it" → MCPWP routes to the agent
- **Status reports** — daily summary of store health

Architecture: WordPress cron pushes to Telegram. Incoming messages handled via webhook → MCP tool call. The mumega-bus integration already exists internally; this surfaces it as a user-facing feature.

---

## Architecture

```
Claude Code / Cursor / Windsurf
        │  Streamable HTTP
        ▼
/wp-json/mcpwp/v3/mcp
        │
        ▼
MCPWP_MCP_Server (PHP)
  ├── MCPWP_Tool_Registry
  │     ├── Built-in tools (content, seo, elementor, woocommerce, site)
  │     ├── Custom tools (from admin UI → mcpwp_custom_tools option)
  │     ├── Plugin tools (from mcpwp_register_tools filter)
  │     └── WP Abilities bridge (auto-discovered)
  ├── MCPWP_Tool_Loader        ← lazy category loading
  ├── MCPWP_Data_Sources       ← GSC, GA, custom APIs (resources)
  ├── MCPWP_Auth               ← API key + scope
  ├── MCPWP_Approval_Gate      ← human approval for destructive ops
  └── MCPWP_Notification       ← Telegram / Discord push

Claude Desktop
        │  stdio
        ▼
mcpwp (npm proxy) → /wp-json/mcpwp/v3/mcp (same endpoint)
```

---

## Tool Interface

```php
interface MCPWP_Tool {
    public function get_name(): string;
    public function get_description(): string;
    public function get_category(): string;
    public function get_input_schema(): array;
    public function get_tier(): string;           // 'free' | 'pro'
    public function get_required_scope(): string; // 'read' | 'write' | 'admin'
    public function requires_approval(): bool;
    public function execute( array $params, WP_REST_Request $request ): array;
}
```

---

## New Plugin Structure

```
mcpwp/
├── mcpwp.php
├── includes/
│   ├── mcp/
│   │   ├── class-mcpwp-server.php          # MCP protocol: initialize, tools/*
│   │   ├── class-mcpwp-transport.php       # HTTP + SSE
│   │   └── class-mcpwp-tool-loader.php     # Lazy category loading
│   ├── core/
│   │   ├── class-mcpwp-tool-registry.php   # Singleton tool store
│   │   ├── class-mcpwp-tool.php            # Interface
│   │   ├── class-mcpwp-custom-tool.php     # Hydrated from options
│   │   ├── class-mcpwp-data-source.php     # Interface for external data
│   │   ├── class-mcpwp-abilities-bridge.php
│   │   └── class-mcpwp-notification.php    # Telegram/Discord push
│   ├── tools/
│   │   ├── content/                        # Posts, pages, media, drafts, menus
│   │   ├── seo/                            # Audit, readiness, autofix, search perf
│   │   ├── elementor/                      # Basic + Pro
│   │   ├── woocommerce/                    # Products, orders, SEO, analytics
│   │   ├── site/                           # Info, state, approvals, playbooks
│   │   └── browser/                        # Navigate, click, crawl (Pro)
│   ├── sources/
│   │   ├── class-mcpwp-source-gsc.php      # Google Search Console
│   │   ├── class-mcpwp-source-ga.php       # Google Analytics
│   │   └── class-mcpwp-source-custom.php   # Custom REST API
│   └── admin/
│       ├── class-mcpwp-admin.php
│       └── class-mcpwp-custom-tools-admin.php
└── readme.txt
```

---

## Why Inkwell + MCPWP (Not Just MCPWP Alone)

MCPWP makes WordPress AI-manageable. Inkwell makes it part of the organism. They are not competing — they are the two halves of the same system.

WordPress structurally cannot do what Inkwell does natively:
- **Knowledge graph across sites** — Inkwell's `GraphPort` + `queryNetwork()`. WordPress multi-site is isolated tables.
- **Edge delivery** — Inkwell pre-compiles to Cloudflare KV. WordPress renders PHP from an origin server on every request.
- **A/B testing at edge** — Inkwell resolves KV variants before the page renders. WordPress cannot intercept before PHP boots.
- **Real financial ledger** — Inkwell's GLASS uses immutable D1 tables. WooCommerce orders are WordPress posts.
- **Network learning** — A/B test winners in Inkwell push to Mirror memory and inform other connected sites. WordPress has no cross-site intelligence.
- **MCP from manifests** — Inkwell plugins declare `mcpTools[]`; kernel collects them automatically. MCPWP is building this from scratch for WordPress because WP has no native equivalent.

**Module L is therefore not optional.** It is the bridge that makes MCPWP more than a WordPress plugin — it makes each WordPress install a node in the organism.

---

## Build Modules

Hybrid approach: you + PM design the hard parts; agents build the mechanical parts.

| Module | Owner | Description |
|--------|-------|-------------|
| A — Core scaffold | Agents | New plugin bootstrap, constants, options prefix, loader |
| B — MCP protocol handler | You + PM | `MCPWP_MCP_Server`: initialize, tools/list, tools/call, HTTP transport |
| C — Tool registry + built-in tools | Agents | Port all v2 tools into `MCPWP_Tool` interface, grouped by category |
| D — Dynamic tool loader | You + PM | Meta-tools, lazy loading, fallback design |
| E — Custom tools platform | Agents | Admin UI, store, REST mapper, plugin filter |
| F — WordPress Abilities bridge | Agents | Auto-discover + bridge to registry |
| G — External data sources | Agents | GSC, GA, custom API integrations |
| H — Browser navigation | Agents | Playwright wrapper via screenshot worker |
| I — Telegram/Discord channel | Agents | Push alerts + command routing |
| J — npm rename + publish | Agents | `site-pilot-ai` → `mcpwp`, config path update |
| K — `.mcpb` Desktop Extension | Agents | `manifest.json` + bundle + submit |

---

## Definition of Done

- [ ] `mcpwp` npm package published at v3.0.0
- [ ] Plugin installs from ZIP, activates without errors
- [ ] MCP `initialize` + `tools/list` + `tools/call` work via HTTP
- [ ] Claude Desktop connects via stdio proxy
- [ ] Dynamic loading: agent loads a category, calls a tool in that category
- [ ] Custom tool created in admin UI appears in MCP tool list
- [ ] Third-party plugin registers a tool via filter
- [ ] External data source (GSC) returns data as MCP resource
- [ ] Telegram alert fires on WooCommerce low-stock event
- [ ] Browser navigation returns screenshot + HTML for a given URL
- [ ] All v2 capabilities accessible via v3
- [ ] Plugin Check passes (0 errors) on WP.org build
