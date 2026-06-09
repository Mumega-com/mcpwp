# MCPWP Backlog

> **For Claude:** Read this file at the start of every session. Tasks are tracked as GitHub issues — use `gh issue list --repo Mumega-com/mcpwp --label "status:ready"` to find work. Update issue status as you go. This file is the orientation layer; GH issues are source of truth.
>
> **Project board:** https://github.com/orgs/Mumega-com/projects/1
> **Quick queue:** `gh issue list --repo Mumega-com/mcpwp --label "status:ready" --state open`

**Current version:** v2.8.50 (main)
**Last updated:** 2026-06-09

---

## Milestone Map

```
M1: Launch-Ready          ← AGENT DONE — awaiting Hadi gates
M2: Multi-Client Distribution (v2.9)
M3: Auth Layer (v3.0)
M4: Hosted Agent + Resold Compute (v3.2)
M5: Content Engine (v4.0)
M6: Platform Foundations (v5.0) — rebrand + microkernel
```

---

## M1 — Launch-Ready (next milestone)

### What blocks launch — hard stops

| # | GH | Who | Task | Status |
|---|-----|-----|------|--------|
| ~~T85~~ | #410 | agent | ~~Fix 29 Dependabot alerts~~ | ✅ PR #438 |
| T86 | #416 | Hadi | Privacy policy at mcpwp.net/privacy — blocks WP.org + ChatGPT App + Claude Connector | ⏳ |
| T87 | #417 | Hadi | Pricing page on mcpwp.net — #1 PH question | ⏳ |
| T88 | #418 | Hadi | Install flow test: zero → first tool call < 5 min | ⏳ |

### Security hardening — must ship before launch

| # | GH | Who | Task | Status |
|---|-----|-----|------|--------|
| ~~T94~~ | #424 | agent | ~~Body size limit + Content-Type enforcement in proxy worker~~ | ✅ PR #438 |
| ~~T95~~ | #425 | agent | ~~Security response headers (nosniff, X-Frame-Options, CSP)~~ | ✅ PR #438 |
| ~~T96~~ | #426 | agent | ~~Workers Rate Limiting binding on /mcp + /api/accounts~~ | ✅ PR #438 |
| ~~T97~~ | #427 | agent | ~~Timing-safe ADMIN_SECRET comparison~~ | ✅ PR #438 |
| T98 | #428 | Hadi | WAF edge rate-limit rule (Cloudflare dashboard — 2 rules) | ⏳ |

### Contributor DX — should be live at launch (contributors arrive)

| # | GH | Who | Task | Status |
|---|-----|-----|------|--------|
| ~~T80~~ | #411 | agent | ~~GitHub issue templates (bug / feature / new-tool)~~ | ✅ PR #438 |
| ~~T81~~ | #412 | agent | ~~PR template~~ | ✅ PR #438 |
| ~~T82~~ | #413 | agent | ~~GitHub Actions CI (PHP lint + smoke test)~~ | ✅ PR #438 |
| ~~T83~~ | #414 | agent | ~~AGENTS.md for AI contributors~~ | ✅ PR #438 |
| ~~T84~~ | #415 | agent | ~~Devcontainer for GitHub Codespaces~~ | ✅ PR #438 |

### Launch assets + community

| # | GH | Who | Task | Status |
|---|-----|-----|------|--------|
| T89 | #419 | Hadi | Email capture on mcpwp.net | ⏳ |
| ~~T90~~ | #420 | agent | ~~Launch blog post~~ | ✅ PR #446 — docs/blog-launch-post.md |
| T91 | #421 | Hadi | Discord server setup | ⏳ |
| T92 | #422 | Hadi | Freemius onboarding email update | ⏳ |
| T40 | #394 | Hadi | Record demo video (90s) | ⏳ |
| T41 | #395 | Hadi | Product Hunt gallery images (5x) | ⏳ |
| T42 | #396 | Hadi | Update mcpwp.net landing page | ⏳ |
| ~~T43~~ | #402 | agent | ~~PH maker first comment~~ | ✅ PR #446 — docs/ph-maker-comment.md |
| T44 | #403 | Hadi | Twitter teaser thread | ⏳ |
| T46 | #404 | Hadi | Set Product Hunt launch date | ⏳ |

### What I (Claude) lack to complete M1

| Need | Why blocked |
|------|-------------|
| Demo site URL | Live public WP (not localhost) — needed for ChatGPT GPT test + video recording |
| ChatGPT Plus account | Custom GPT creation + Developer Mode MCP test |
| PH account | Hadi must be the maker |
| Pricing decision | Can't write pricing page without numbers |
| Privacy policy review | Can draft, Hadi must approve legal text |
| WP.org account | Hadi submits manually |

**Agent tasks remaining in M1:** none — all done. Launch blocked on Hadi gates above.

---

## M2 — Multi-Client Distribution (v2.9)

### Depends on: M1 complete, T03/T04 testing passed

| # | GH | Task |
|---|-----|------|
| T01 | #378 | Test Claude Desktop + Claude Code (local) |
| T02 | #379 | Test Claude Code against localhost:8080 |
| T03 | #380 | Test OpenClaw streamable-http + X-API-Key header (bug #65590) |
| T04 | #381 | Test Hermes Agent + Tool Search activation |
| ~~T10~~ | #383 | ~~Update openapi-chatgpt.yaml for v2.8.49~~ — ✅ PR #446 (now v2.8.50, 49 ops) |
| T11 | — | Create ChatGPT Custom GPT in GPT Store |
| T13 | #385 | integrations/chatgpt/ setup guide |
| T20 | #388 | WP.org audit (free tier, GPL clean) |
| T21 | #389 | Build WP.org free zip |
| T23 | #391 | Update readme.txt for WP.org |
| T25 | — | Submit to WP.org |
| T30 | — | Publish ClawHub skill (needs T03 pass) |
| T31 | — | Publish Hermes Skills Hub skill (needs T04 pass) |
| T32 | #397 | Submit to MCP registries (mcp.so, smithery.ai, glama.ai) |
| T34 | #398 | Claude Desktop Extension (MCPB format) |
| T50 | #405 | MCP Resources |
| T51 | #406 | MCP Prompts |
| ~~T52~~ | #407 | ~~BM25-optimized tool descriptions~~ — ✅ PR #446 (56 descriptions rewritten) |

### What I lack for M2

| Need | Why |
|------|-----|
| ChatGPT Plus | GPT Store publishing |
| Test results T03/T04 | Can't publish ClawHub/Hermes skills blind |
| WP.org account | Submission |

---

## M2.5 — Custom Tool Registry (v2.9 add-on, can ship independently)

MCPWP becomes a platform runtime. Third-party plugins (Digid, WooCommerce extensions, booking plugins) register their own MCP tools. Every WP site becomes a programmable agent surface specific to that business.

| # | GH | Task | Notes |
|---|-----|------|-------|
| ~~T71a~~ | #439 | ~~Plugin hook API (`spai_register_tools` filter)~~ | ✅ PR #446 — Spai_Custom_Tool_Registry, dispatch via rest_path |
| T71b | #440 | REST proxy tools (no-code external endpoints) | Admin defines tool → MCPWP proxies to external URL. No PHP needed. |
| T71c | #441 | Visual tool builder UI (WP Admin sandbox) | Form-based tool creator with inline test panel. Layer on top of T71b. |

**T71a can ship in M2 — independent of OAuth, no blockers.**

---

## M3 — Auth Layer (v3.0)

### Depends on: M2 shipped

| # | GH | Task | Notes |
|---|-----|------|-------|
| T60 | #408 | OAuth 2.1 central CF Worker (auth.mcpwp.net) | Unlocks ChatGPT App + Claude Connector + YouTube + GHL |
| T61 | — | ChatGPT App Directory submission | Needs T60 |
| T62 | — | Claude Connector directory submission | Needs T60 |

### What I lack for M3

OAuth 2.1 + PKCE is a significant build. Needs Opus-level spec session before implementation.

---

## M4 — Hosted Agent + Resold Compute (v3.2)

### Depends on: M3 (OAuth layer)

| # | GH | Task | Notes |
|---|-----|------|-------|
| T100 | #429 | McpAgent Worker (agent.mcpwp.net) | Stateful DO per session. BYOK or pooled Workers AI. **Spec: Opus** |
| T101 | #430 | AI Gateway for all LLM calls | Free logging + per-site cost tracking |
| T102 | #431 | Vectorize semantic tool search | 85% token reduction per agent turn |
| T113 | #435 | Telegram bot client | First chat platform — no OAuth, inline approval keyboards |
| T114 | #436 | Slack bot client | Team member play for agencies |
| T115 | #437 | Discord bot client | Developer/OSS audience |

---

## M5 — Content Engine (v4.0)

### Depends on: M4 (McpAgent + OAuth)

| # | GH | Task | Notes |
|---|-----|------|-------|
| T110 | #432 | Content Engine spec (post → video → YouTube → GHL) | **Spec: Opus** |
| T111 | #433 | Remotion branded video templates | React → MP4, brand props from wp_remember |
| T112 | #434 | GoHighLevel integration | Social + CRM, OAuth via T60 |

---

## M6 — Platform Foundations (v5.0)

### Depends on: M5 shipped, community stable

Clean-slate internals. One breaking release, done right.

| # | GH | Task | Notes |
|---|-----|------|-------|
| T126 | — | Rebrand: `spai_` → `mcpwp_`, REST `site-pilot-ai/v1` → `mcpwp/v1` | Deprecation shims for one major version. **Spec: Opus** |
| T127 | — | Microkernel refactor: dissolve monolithic tool classes into self-registering modules | Each tool category = one `module.php` using `mcpwp_register_tools`. Same pattern as third-party. **Spec: Opus** |
| T128 | — | `mcpwp:dev` skill (updated post-microkernel) | Agent onboarding for the new module pattern |

**Rule:** T126 + T127 ship together in v5.0. One breaking change window, not two.

---

## Technical Debt (fix as we go)

| # | GH | Severity | Task |
|---|-----|----------|------|
| T120 | #442 | medium | Split free/pro tool files — 2000+ line classes block contributors |
| T121 | #443 | high | PHPUnit tests — zero PHP test coverage, silent deploy failures |
| ~~T122~~ | #444 | high | ~~Rewrite 120+ tool descriptions for BM25/vector accuracy~~ | ✅ PR #446 |
| ~~T123~~ | #445 | launch-blocker | ~~Update openapi-chatgpt.yaml to v2.8.49~~ | ✅ PR #446 (v2.8.50) |
| T124 | — | medium | `mcpwp:dev` skill — agent onboarding for plugin dev: add-a-tool pattern, version bump 3-file rule, local test stack, CI, `spai_register_tools` usage |
| T125 | — | medium | Update `mumcp:tools` skill — stale at 239 tools, missing custom tool registry, missing new endpoints |

---

## Shipped (v2.8.45–v2.8.50)

- Server-side PostHog analytics
- Agency multi-site proxy (CF Worker)
- AI action audit log + rollback (EU AI Act)
- Agency dashboard
- White-label branding + `[mcpwp_chat]` shortcode
- Dynamic site memory (`wp_remember` / `wp_recall`)
- Proactive signals (`wp_get_signals`)
- Site blueprint library (5 starters)
- Chat excellence (multi-model, SSE, history)
- **v2.8.50:** `spai_register_tools` filter hook API; 56 BM25-optimized tool descriptions; openapi-chatgpt.yaml 49 operations; 5 pre-existing PHP syntax errors cleared; test bootstrap fixed
- README rebuilt (OpenClaw/Hermes/ChatGPT sections)
- ClawHub skill (`integrations/clawhub/SKILL.md`)
- Hermes integration (`integrations/hermes/`)
- GH label taxonomy + 60+ issues filed

---

## Architecture Notes

### T126 — Rebrand (`spai_` → `mcpwp_`)

Every public surface uses the wrong prefix today:
- WP option keys: `spai_api_keys`, `spai_settings`, `spai_site_uuid`, …
- Hook names: `spai_tool_called`, `spai_register_rest_routes`, `spai_register_tools`, …
- REST namespace: `site-pilot-ai/v1` (should be `mcpwp/v1`)
- Class names: `Spai_*` throughout
- MCP tool names: `wp_*` (these are fine — keep)

**Breaking change scope:** Any site with custom hooks on `spai_*` or hitting `site-pilot-ai/v1` directly breaks. Must ship with:
1. Deprecated aliases for all old option keys (copy on read, write new)
2. Old REST namespace forwarded to new for one major version
3. `spai_tool_called` → fires both old + new name during transition

**When:** v3.0, after WP.org submission. Do not do before launch — WP.org review + existing installs.

### T127 — Microkernel Refactor

Current structure: two monolithic classes (`Spai_MCP_Free_Tools`, `Spai_MCP_Pro_Tools`) each 2000+ lines, extending a base registry. All 250+ tools live in two files.

**Target structure (microkernel):**
```
includes/
  kernel/
    class-mcpwp-kernel.php        # bootstrap, hook registry, dispatch
    class-mcpwp-tool-dispatch.php # MCP tool routing (replaces class-spai-rest-mcp.php core)
  modules/
    pages/       module.php       # registers wp_create_page, wp_update_page, …
    elementor/   module.php       # registers wp_get_elementor, wp_set_elementor, …
    media/       module.php
    seo/         module.php
    memory/      module.php
    blueprints/  module.php
    …
  api/           (unchanged — REST controllers stay)
```

Each `module.php`:
```php
add_filter('mcpwp_register_tools', function($tools) {
    $tools[] = [ 'name' => 'wp_create_page', ... ];
    return $tools;
});
```

**Result:**
- T120 (split files) becomes the first migration step
- Free/Pro split is a capability filter on the module, not a class hierarchy
- Third-party plugins (Digid, WooCommerce ext) use exact same pattern as built-ins — no special case
- Contributors can add a tool by editing one focused module file
- Modules are independently testable

**Dependency:** T126 (rebrand) should ship same release — do both as v3.0.
**Spec:** Needs Opus session before implementation. Not mechanical work.

---

## Decisions Made

| Decision | Rationale |
|----------|-----------|
| ChatGPT Custom GPT first, App Store later | Custom GPT works with X-API-Key today. App Store needs OAuth. |
| OAuth via central CF Worker | One submission URL. One OAuth flow per user. |
| Claude Connector via Desktop Extension first | MCPB handles per-user URL + API key — ships before OAuth. |
| Don't publish ClawHub/Hermes until tested | OpenClaw bug #65590. Ship quality, not speed. |
| WP.org free version separate build | `scripts/build-wporg.sh` strips pro. Don't submit full zip. |
| Close premium to private repo | Standard commercial WP model. Decision pending (#423). |

---

## Needs From Hadi

| Item | Blocks |
|------|--------|
| Confirm pricing (Free/Pro/Agency numbers) | T87 pricing page, T92 onboarding email |
| Demo site (live public WP) | ChatGPT GPT test, video recording |
| ChatGPT Plus account | T11 Custom GPT, T15 App Store |
| Product Hunt account + launch date | T46 |
| Discord server creation | T91 |
| WAF rules in CF dashboard | T98 |
| Privacy policy approval | T86 |
| WP.org account | T25 |
| Premium repo decision | T93 |

---

## Key Files

| File | Purpose |
|------|---------|
| `BACKLOG.md` | This file |
| `docs/FREE_PRO_SPLIT.md` | Free vs Pro tool tier |
| `docs/openapi-chatgpt.yaml` | ChatGPT schema (stale, needs T10) |
| `integrations/clawhub/SKILL.md` | OpenClaw ClawHub skill |
| `integrations/hermes/SKILL.md` | Hermes Skills Hub skill |
| `integrations/hermes/README.md` | Hermes setup guide |
