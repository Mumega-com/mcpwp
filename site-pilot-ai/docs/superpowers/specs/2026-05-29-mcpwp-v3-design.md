# MCPWP v3 — Architecture Design
**Date:** 2026-05-29  
**Status:** Draft  
**Owner:** Product (Hadi / Mumega)  
**Target:** mcpwp.net v3 — clean break from v2

---

## What Changes in v3

| Concern | v2 | v3 |
|---------|----|----|
| Plugin slug | `site-pilot-ai` | `mcpwp` |
| REST namespace | `site-pilot-ai/v1` | `mcpwp/v3` |
| Option prefix | `spai_` | `mcpwp_` |
| Class prefix | `Spai_` | `MCPWP_` |
| MCP transport | Node.js stdio proxy → PHP REST | PHP native (HTTP + stdio proxy) |
| Tool list size | ~200 tools at connect | 2 meta-tools at connect, groups on demand |
| Custom tools | Not possible | Admin UI + plugin hooks |
| Node.js proxy | 240-line pass-through | Kept, renamed `mcpwp` on npm |

**Migration:** Internal only (5 sites). Reinstall. No compatibility shim.

---

## Three Bets

### Bet 1 — Thin MCP (Dynamic Tool Loading)

**Problem:** Every MCP connection sends all 200+ tool schemas to the AI client upfront. On a 200k-context model that's thousands of tokens wasted before the agent does anything.

**Solution:** Lazy category loading. At connect time the agent sees exactly 2 tools:

```
mcpwp.list_categories  → returns: ["content", "seo", "elementor", "woocommerce", "custom", ...]
mcpwp.use_category     → loads full tool schemas for one category into context
```

The agent calls `use_category("seo")` when doing SEO work. It never pays for WooCommerce or Elementor schemas in that session.

**Implementation:** PHP-side. `tools/list` returns only the 2 meta-tools by default. `mcpwp.use_category` is a real MCP tool that calls `tools/list` internally and returns the schemas as its result — the agent reads them and knows the tools exist. This works within the MCP spec without any proxy tricks.

**Opt-out:** Sites can set `mcpwp_tool_loading: eager` to get the full list upfront (backwards-compatible mode).

---

### Bet 2 — PHP-Native HTTP Transport

**Problem:** Claude Desktop requires stdio. Streamable HTTP clients (Claude Code, Cursor, Windsurf) don't. The Node.js proxy exists only to bridge stdio → HTTP. For HTTP clients, it's unnecessary overhead.

**Solution:** Expose a proper MCP HTTP endpoint at `/wp-json/mcpwp/v3/mcp` that handles the full MCP protocol natively:

- `POST /wp-json/mcpwp/v3/mcp` — handles `initialize`, `tools/list`, `tools/call`
- `GET /wp-json/mcpwp/v3/mcp` — SSE stream for server-initiated messages
- Auth: existing `X-API-Key` header

**Node.js proxy:** Keep it, rename npm package to `mcpwp`, version to match plugin. It stays as the stdio bridge for Claude Desktop. Zero new logic — it already just passes through. Update `--help` strings and the config file path to `~/.mcpwp/config.json`.

**Result:** Streamable HTTP clients connect directly to WordPress. Claude Desktop uses the proxy. Same PHP tool registry serves both.

---

### Bet 3 — Custom Tools Platform

**Problem:** MCPWP ships 200 tools. Every other plugin's functionality is invisible to AI agents.

**Solution:** Three layers of extensibility:

#### Layer 1 — Admin UI (site owner, no code)
New admin screen: **MCPWP → Custom Tools**

Site owner defines a tool:
- Name, description, category
- Input schema (JSON schema builder)
- Handler: REST endpoint URL + method + parameter mapping

Stored in `wp_options` as `mcpwp_custom_tools`. Appears in the tool list immediately.

#### Layer 2 — Plugin Hook (developer)
Any WordPress plugin registers tools via filter:

```php
add_filter( 'mcpwp_register_tools', function( $registry ) {
    $registry->add( 'my_plugin.do_thing', [
        'description' => 'Does a thing in My Plugin',
        'category'    => 'my_plugin',
        'input_schema' => [ 'type' => 'object', 'properties' => [] ],
        'handler'     => [ My_Plugin_Handler::class, 'do_thing' ],
        'tier'        => 'free',
    ] );
    return $registry;
} );
```

#### Layer 3 — WordPress Abilities Bridge (automatic)
WordPress 6.x ships the Abilities API (`WordPress/mcp-adapter`). Any plugin that registers a WordPress Ability gets auto-bridged into MCPWP's tool list. No configuration needed.

---

## Architecture

```
MCP Client (Claude Code, Cursor)
        │  Streamable HTTP
        ▼
/wp-json/mcpwp/v3/mcp  ←── WordPress REST API
        │
        ▼
MCPWP_MCP_Server (PHP)
  ├── MCPWP_Tool_Registry        ← central tool store
  │     ├── Built-in tools       ← content, seo, elementor, woocommerce...
  │     ├── Custom tools         ← from wp_options (admin UI)
  │     ├── Plugin tools         ← from mcpwp_register_tools filter
  │     └── WP Abilities bridge  ← auto-discovered
  ├── MCPWP_Tool_Loader          ← lazy category loading
  ├── MCPWP_Auth                 ← API key + scope validation
  └── MCPWP_Approval_Gate        ← human approval for destructive ops


MCP Client (Claude Desktop)
        │  stdio
        ▼
mcpwp (npm, Node.js proxy)
        │  HTTP
        ▼
/wp-json/mcpwp/v3/mcp  (same endpoint)
```

---

## Tool Registry Interface

Every tool in v3 implements a single contract:

```php
interface MCPWP_Tool {
    public function get_name(): string;
    public function get_description(): string;
    public function get_category(): string;
    public function get_input_schema(): array;
    public function get_tier(): string;          // 'free' | 'pro'
    public function get_required_scope(): string; // 'read' | 'write' | 'admin'
    public function requires_approval(): bool;
    public function execute( array $params, WP_REST_Request $request ): array;
}
```

The registry is a singleton: `MCPWP_Tool_Registry::get_instance()`. Built-in tools are PHP classes. Custom tools from the admin UI are `MCPWP_Custom_Tool` instances hydrated from `wp_options`. Plugin tools are registered at `init` via the filter.

---

## New Plugin Structure

```
mcpwp/
├── mcpwp.php                          # Bootstrap, MCPWP_VERSION constant
├── includes/
│   ├── mcp/
│   │   ├── class-mcpwp-server.php     # MCP protocol handler (initialize, tools/*)
│   │   ├── class-mcpwp-transport.php  # HTTP + SSE transport
│   │   └── class-mcpwp-tool-loader.php # Lazy category loading
│   ├── core/
│   │   ├── class-mcpwp-tool-registry.php  # Central tool registry
│   │   ├── class-mcpwp-tool.php           # Interface
│   │   ├── class-mcpwp-custom-tool.php    # Custom tool from admin UI
│   │   └── class-mcpwp-abilities-bridge.php # WP Abilities → MCPWP tools
│   ├── tools/                         # Built-in tool implementations
│   │   ├── content/                   # Posts, pages, media, drafts, menus
│   │   ├── seo/                       # SEO audit, readiness, autofix
│   │   ├── elementor/                 # Elementor basic + pro
│   │   ├── woocommerce/               # Products, orders, SEO
│   │   └── site/                      # Site info, state, approvals
│   ├── admin/
│   │   ├── class-mcpwp-admin.php
│   │   └── class-mcpwp-custom-tools-admin.php  # Custom tools UI
│   └── api/
│       └── class-mcpwp-rest-api.php   # REST route registration
├── admin/
│   ├── js/mcpwp-admin.js
│   └── partials/
│       ├── mcpwp-dashboard.php
│       └── mcpwp-custom-tools.php
└── readme.txt
```

---

## Build Modules (Implementation Order)

### Module A — Core scaffold (agents)
New plugin bootstrap, constants, loader, options migration from `spai_` → `mcpwp_`. Empty tool registry. Admin menu shell.

### Module B — MCP protocol handler (you + me)
`MCPWP_MCP_Server` implementing `initialize`, `tools/list` (meta-tools only), `tools/call`. HTTP transport. Auth. This is the architectural core — we design the protocol handling together.

### Module C — Tool registry + built-in tools (agents)
Port all existing v2 tools into the new `MCPWP_Tool` interface. Group into categories. One class per tool is verbose — group related operations into handler classes (e.g. `MCPWP_Content_Tools` handles posts, pages, media).

### Module D — Dynamic tool loader (you + me)
`mcpwp.list_categories` and `mcpwp.use_category` meta-tools. The `use_category` tool returns tool schemas as its response — agent reads and uses them. Edge case: what happens when agent tries to call a tool it loaded dynamically but isn't in the `tools/list`? We design the fallback together.

### Module E — Custom tools platform (agents)
Admin UI for creating/editing custom tools. `MCPWP_Custom_Tool_Store` backed by `wp_options`. `mcpwp_register_tools` filter. REST endpoint → MCP tool mapper.

### Module F — WordPress Abilities bridge (agents)
Auto-discover registered WordPress Abilities and bridge them into the tool registry. Requires `WordPress/mcp-adapter` to be installed (graceful degradation if not).

### Module G — Node.js proxy rename (agents)
Rename npm package to `mcpwp`. Update config path to `~/.mcpwp/config.json`. Update `--help` strings. Bump version to `3.0.0`. Publish to npm.

### Module H — `.mcpb` Desktop Extension (agents)
Create `manifest.json`. Package `dist/index.js` into `.mcpb` bundle. Submit to Claude Desktop extensions directory.

---

## Custom Tools Admin UI (sketch)

```
MCPWP → Custom Tools

  [ + New Tool ]

  ┌─────────────────────────────────────────────────────┐
  │ Name:         get_active_clients                     │
  │ Description:  Returns active clients from CRM        │
  │ Category:     custom                                 │
  │                                                      │
  │ Handler type: ● REST Endpoint  ○ WordPress Hook      │
  │                                                      │
  │ URL:          /wp-json/my-crm/v1/clients             │
  │ Method:       GET                                    │
  │                                                      │
  │ Input parameters:                                    │
  │   status (string, optional) → query param ?status=  │
  │                                                      │
  │ Requires approval: ☐                                 │
  │ Tier:              Free                              │
  │                                          [ Save ]   │
  └─────────────────────────────────────────────────────┘
```

---

## What We Don't Build in v3

- Agency dashboard (v3.1)
- Figma OAuth (stays as-is from v2)
- Multi-language (stays as-is)
- Centralised capability registry enforcement (tracked, not blocking launch)

---

## Definition of Done

- [ ] `mcpwp` npm package published
- [ ] Plugin installs from ZIP, activates without errors
- [ ] MCP `initialize` + `tools/list` + `tools/call` work via HTTP
- [ ] Claude Desktop connects via stdio proxy
- [ ] Dynamic loading: agent loads a category, calls a tool in that category
- [ ] Custom tool created in admin UI appears in MCP tool list
- [ ] Third-party plugin registers a tool via `mcpwp_register_tools` filter
- [ ] All v2 capabilities accessible via v3 tool names
- [ ] Plugin Check passes (0 errors) on WP.org build
