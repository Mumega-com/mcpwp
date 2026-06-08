# MCPWP Backlog

> **For Claude:** Read this file at the start of every session. Tasks are tracked as GitHub issues — use `gh issue list --repo Mumega-com/mcpwp --label "status:ready"` to find work. Update issue status as you go. This file is the orientation layer; GH issues are source of truth.
>
> **Project board:** https://github.com/orgs/Mumega-com/projects/1
> **Quick queue:** `gh issue list --repo Mumega-com/mcpwp --label "status:ready" --state open`

**Current version:** v2.8.49 (main)
**Last updated:** 2026-06-08

---

## Milestone Map

```
M1: Launch-Ready ← YOU ARE HERE
M2: Multi-Client Distribution (v2.9)
M3: Auth Layer (v3.0)
M4: Hosted Agent + Resold Compute (v3.2)
M5: Content Engine + Chat Platforms (v4.0)
```

---

## M1 — Launch-Ready (next milestone)

### What blocks launch — hard stops

| # | GH | Who | Task |
|---|-----|-----|------|
| T85 | #410 | agent | Fix 29 Dependabot alerts (2 critical, 6 high) — visible on GitHub |
| T86 | #416 | Hadi | Privacy policy at mcpwp.net/privacy — blocks WP.org + ChatGPT App + Claude Connector |
| T87 | #417 | Hadi | Pricing page on mcpwp.net — #1 PH question |
| T88 | #418 | Hadi | Install flow test: zero → first tool call < 5 min |

### Security hardening — must ship before launch

| # | GH | Who | Task |
|---|-----|-----|------|
| T94 | #424 | agent | Body size limit + Content-Type enforcement in proxy worker |
| T95 | #425 | agent | Security response headers (nosniff, X-Frame-Options, CSP) |
| T96 | #426 | agent | Workers Rate Limiting binding on /mcp + /api/accounts |
| T97 | #427 | agent | Timing-safe ADMIN_SECRET comparison |
| T98 | #428 | Hadi | WAF edge rate-limit rule (Cloudflare dashboard — 2 rules) |

### Contributor DX — should be live at launch (contributors arrive)

| # | GH | Who | Task |
|---|-----|-----|------|
| T80 | #411 | agent | GitHub issue templates (bug / feature / new-tool) |
| T81 | #412 | agent | PR template |
| T82 | #413 | agent | GitHub Actions CI (PHP lint + smoke test) |
| T83 | #414 | agent | AGENTS.md for AI contributors |
| T84 | #415 | agent | Devcontainer for GitHub Codespaces |

### Launch assets + community

| # | GH | Who | Task |
|---|-----|-----|------|
| T89 | #419 | Hadi | Email capture on mcpwp.net |
| T90 | #420 | agent | Launch blog post |
| T91 | #421 | Hadi | Discord server setup |
| T92 | #422 | Hadi | Freemius onboarding email update |
| T40 | #394 | Hadi | Record demo video (90s) |
| T41 | #395 | Hadi | Product Hunt gallery images (5x) |
| T42 | #396 | Hadi | Update mcpwp.net landing page |
| T43 | #402 | agent | PH maker first comment |
| T44 | #403 | Hadi | Twitter teaser thread |
| T46 | #404 | Hadi | Set Product Hunt launch date |

### What I (Claude) lack to complete M1

| Need | Why blocked |
|------|-------------|
| Demo site URL | Live public WP (not localhost) — needed for ChatGPT GPT test + video recording |
| ChatGPT Plus account | Custom GPT creation + Developer Mode MCP test |
| PH account | Hadi must be the maker |
| Pricing decision | Can't write pricing page without numbers |
| Privacy policy review | Can draft, Hadi must approve legal text |
| WP.org account | Hadi submits manually |

**What agent can do unblocked right now:** T85, T80-T84, T90, T94-T97, T43

---

## M2 — Multi-Client Distribution (v2.9)

### Depends on: M1 complete, T03/T04 testing passed

| # | GH | Task |
|---|-----|------|
| T01 | #378 | Test Claude Desktop + Claude Code (local) |
| T02 | #379 | Test Claude Code against localhost:8080 |
| T03 | #380 | Test OpenClaw streamable-http + X-API-Key header (bug #65590) |
| T04 | #381 | Test Hermes Agent + Tool Search activation |
| T10 | #383 | Update openapi-chatgpt.yaml for v2.8.49 |
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
| T52 | #407 | BM25-optimized tool descriptions |

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
| T71a | #439 | Plugin hook API (`spai_register_tools` filter) | Digid registers own tools in 10 lines of PHP. ~2–3h build. Ships first. |
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

## Shipped (v2.8.45–v2.8.49)

- Server-side PostHog analytics
- Agency multi-site proxy (CF Worker)
- AI action audit log + rollback (EU AI Act)
- Agency dashboard
- White-label branding + `[mcpwp_chat]` shortcode
- Dynamic site memory (`wp_remember` / `wp_recall`)
- Proactive signals (`wp_get_signals`)
- Site blueprint library (5 starters)
- Chat excellence (multi-model, SSE, history)
- README rebuilt (OpenClaw/Hermes/ChatGPT sections)
- ClawHub skill (`integrations/clawhub/SKILL.md`)
- Hermes integration (`integrations/hermes/`)
- GH label taxonomy + 60+ issues filed

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
