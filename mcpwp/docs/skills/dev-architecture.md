---
name: dev-architecture
description: Top-level map of the MCPWP plugin codebase — the load order, the layers, and which per-area dev skill to open next. Load this FIRST when starting work anywhere in the plugin.
---

# MCPWP Dev — Architecture Index

> Load this first. Then open the per-area skill for whatever you're touching.
> Post-rebrand v3.0.0 naming everywhere: classes `Mcpwp_*`, funcs/hooks/options `mcpwp_*`,
> constants `MCPWP_*`, REST namespace `mcpwp/v1`, API key prefix `mcpwp_`, text domain `mcpwp`.
> There are **no** `spai_` / `site-pilot-ai` / `mumcp` aliases — those names are dead.

## The plugin in one breath

A WordPress plugin that exposes content, design, SEO, and admin operations to AI agents over
two surfaces: a **REST API** (`mcpwp/v1`, key-authed) and an **MCP server** (`/mcp` JSON-RPC,
~272 tools). Agents bring their own compute (Claude Code, Codex, ChatGPT); the plugin is the
gateway to the site. Free tier is wp.org/Freemius-free; pro tier is Freemius-licensed.

## Layered architecture

```
        AI agent (Claude Code / Codex / ChatGPT)
                     │  HTTPS + X-API-Key
        ┌────────────▼─────────────┐
        │  api/  REST controllers  │  ← namespace mcpwp/v1; base = Mcpwp_REST_API
        │  - class-mcpwp-rest-mcp  │  ← /mcp dispatch: tools/list, tools/call
        └────────────┬─────────────┘
                     │ dispatches tool name → handler
        ┌────────────▼─────────────┐
        │  mcp/  tool registries   │  ← define_tool(); free + pro + custom (hook)
        └────────────┬─────────────┘
                     │ handlers call services
        ┌────────────▼─────────────┐
        │  core/  domain services  │  ← Elementor, analytics, SEO, content, providers…
        │  pro/   premium services │  ← Woo, LearnPress, Elementor Pro, page-builder
        └────────────┬─────────────┘
                     │
        ┌────────────▼─────────────┐
        │  traits/ + infra         │  ← auth, sanitize, log, rate-limit, loader, updater
        └──────────────────────────┘
        admin/  ← WP-admin UI (separate vertical: pages, settings, design system)
```

Request lifecycle: WP routes a REST request → `Mcpwp_REST_API` validates the `mcpwp_`-prefixed
key (auth trait) → the controller (or `/mcp` dispatcher) runs → calls into `core/`/`pro/`
services → fires `do_action( 'mcpwp_tool_called', $tool, $category, $duration_ms, $error_code )`
for analytics → returns a standard JSON envelope.

## Directory → skill map

| Area | Dir | ~Lines | Load this skill |
|------|-----|--------|-----------------|
| Domain / service layer | `mcpwp/includes/core/` | 18k | [[dev-core]] |
| REST controllers | `mcpwp/includes/api/` | 18k | [[dev-api]] |
| MCP tool registries | `mcpwp/includes/mcp/` | 8.4k | [[dev-mcp-tools]] |
| Admin UI | `mcpwp/includes/admin/` + `mcpwp/admin/` | 5.5k | [[dev-admin]] |
| Pro features + licensing | `mcpwp/includes/pro/` + `freemius-init.php` | — | [[dev-pro]] |
| Cross-cutting infra | `mcpwp/includes/traits/` + bootstrap/loader/updater | 1.9k+ | [[dev-traits-infra]] |

## Bootstrap & load order

`mcpwp/mcpwp.php` is the entry: defines `MCPWP_VERSION` + path constants, `require`s the classes,
and wires hooks through `Mcpwp_Loader` (an add_action/add_filter collector with a `run()`).
See [[dev-traits-infra]] for the exact sequence.

## The biggest files (work-with-care list)

| File | Lines | Why it's big |
|------|-------|--------------|
| `api/class-mcpwp-rest-site.php` | 4531 | catch-all site surface (/onboard, /site-info, /settings, /introspect…) |
| `mcp/class-mcpwp-mcp-free-tools.php` | 3992 | ~160 free tool definitions |
| `admin/class-mcpwp-admin.php` | 3491 | every admin page render in one class |
| `mcp/class-mcpwp-mcp-pro-tools.php` | 3332 | ~113 pro tool definitions |
| `core/class-mcpwp-elementor-basic.php` | 3299 | Elementor get/set + validation engine |

These are the targets of the **microkernel refactor** — see `docs/MICROKERNEL-REFACTOR-PLAN.md`
(planned for v5; do not refactor ad-hoc).

## Version bumping (every code change)

Bump in **3 files**, always together: `mcpwp/mcpwp.php` (header `Version:` + `MCPWP_VERSION`),
`mcpwp/readme.txt` (`Stable tag:` + changelog entry), `version.json` (`version` + changelog).
CI (`validate.yml`) fails the build if these three disagree.

## Tests & CI

`mcpwp/tests/` (PHPUnit). Local: `composer install --working-dir mcpwp` then run phpunit;
`php -l` for syntax. CI runs PHP 7.4/8.0/8.1/8.2 validation + lint-and-test + proxy-worker tests
+ syntax — 7 checks, all must be green to merge.

## User-facing agent skills (separate)

The skills agents load to *operate* a site (connect, setup, operate, design, elementor, tools,
status) live in the **mcpwp-claude-plugin** marketplace plugin, not here. These `dev-*` skills are
for working **on the plugin's source**.

## Related
- `docs/MICROKERNEL-REFACTOR-PLAN.md` — the v5 target architecture for the big files
- Root `CLAUDE.md` — full REST endpoint tables, options keys, hooks reference
