# MCPWP Backlog

> **For Claude:** Read this file at the start of every session. Tasks are tracked as GitHub issues — use `gh issue list --repo Mumega-com/mcpwp --label "status:ready"` to find work. Update issue status as you go. This file is the orientation layer; GH issues are the source of truth.
>
> **Project board:** https://github.com/orgs/Mumega-com/projects/1
> **Quick queue:** `gh issue list --repo Mumega-com/mcpwp --label "status:ready" --state open`

**Current version:** v2.8.49 (main)  
**Last updated:** 2026-06-08

---

## Status Legend

| Symbol | Meaning |
|--------|---------|
| ✅ | Done, merged to main |
| 🔄 | In progress |
| ❌ | Not started |
| 🔒 | Blocked — see notes |

---

## Shipped (v2.8.45–v2.8.49)

- ✅ Server-side PostHog analytics
- ✅ Agency multi-site proxy worker
- ✅ AI action audit log + rollback (EU AI Act)
- ✅ Agency dashboard
- ✅ White-label branding + `[mcpwp_chat]` shortcode
- ✅ Dynamic site memory (`wp_remember` / `wp_recall`)
- ✅ Proactive signals (`wp_get_signals`)
- ✅ Site blueprint library (5 starters, deploy + extract)
- ✅ Chat excellence (multi-model, SSE streaming, history)
- ✅ README updated (v2.8.49, all features, OpenClaw/Hermes/ChatGPT sections)
- ✅ `integrations/clawhub/SKILL.md` — ClawHub skill file created
- ✅ `integrations/hermes/README.md` + `SKILL.md` — Hermes Agent integration created

---

## Pre-Launch Gaps (just added — #410–#423)

### Security (BLOCKER)
| # | GH | Task |
|---|-----|------|
| T85 | #410 | Fix 29 Dependabot alerts (2 critical, 6 high) — visible on GitHub, kills launch trust |

### Contributor DX
| # | GH | Task |
|---|-----|------|
| T80 | #411 | GitHub issue templates (bug / feature / new-tool) |
| T81 | #412 | PR template |
| T82 | #413 | GitHub Actions CI (PHP lint + smoke test) |
| T83 | #414 | AGENTS.md for AI contributors |
| T84 | #415 | Devcontainer for GitHub Codespaces |

### Legal / Trust
| # | GH | Task |
|---|-----|------|
| T86 | #416 | Privacy policy page at mcpwp.net/privacy (blocks WP.org, ChatGPT App, Claude Connector) |
| T93 | #423 | DECISION: close premium source to private repo |

### Website / Conversion
| # | GH | Task |
|---|-----|------|
| T87 | #417 | Pricing page on mcpwp.net |
| T88 | #418 | End-to-end install flow test (zero → first tool call, target <5 min) |
| T89 | #419 | Email capture on mcpwp.net (PH traffic spike is one-time) |
| T90 | #420 | Launch blog post ("How we built a 120-tool WP MCP server") |
| T91 | #421 | Discord server + support channel |
| T92 | #422 | Freemius onboarding email update for v2.8.49 |

---

## Active Sprint — Launch Prep

### P0 — Integration Testing (do before any publish)

| # | Task | Status | Notes |
|---|------|--------|-------|
| T01 | Test MCPWP against Claude Desktop (local WP localhost:8080) | ❌ | Generate key, add MCP config, run wp_onboard, verify 120+ tools |
| T02 | Test MCPWP against Claude Code | ❌ | Same config as Desktop |
| T03 | Test OpenClaw streamable-http + X-API-Key header passthrough | ❌ | Known bug #65590 — must confirm headers reach WP before publishing ClawHub skill |
| T04 | Test Hermes Agent config + Tool Search activation | ❌ | Confirm 120+ tools discovered, Tool Search activates |
| T05 | Test ChatGPT Custom GPT with existing openapi-chatgpt.yaml | ❌ | See T10 first — schema needs update |

### P1 — ChatGPT Integration

**Context:** ChatGPT has two paths:
- **Custom GPT + GPT Actions** (GPT Store) — X-API-Key works today, no code change needed. `docs/openapi-chatgpt.yaml` exists but stale (v1.0.60, missing all v2.8.45-2.8.49 tools).
- **ChatGPT App** (App Directory at chatgpt.com/apps) — MCP server + OAuth 2.1 required. Published Oct 2025. 800M+ user reach. X-API-Key NOT supported for App Store listing.
- **Developer Mode MCP** (Plus/Pro users) — paste URL in Settings → Connectors. Static header auth not confirmed in UI; may need OAuth wrapper.

| # | Task | Status | Notes |
|---|------|--------|-------|
| T10 | Update `docs/openapi-chatgpt.yaml` for v2.8.49 (add memory, signals, blueprints, approvals tools) | ❌ | File exists at docs/openapi-chatgpt.yaml — add ~30 missing endpoints |
| T11 | Create ChatGPT Custom GPT in GPT Store using updated schema | ❌ | Needs T10. Auth: API key custom header X-API-Key. Publish as "MCPWP — WordPress Agent" |
| T12 | Test Custom GPT end-to-end against a live site | ❌ | Needs T11 |
| T13 | Add `integrations/chatgpt/` folder with setup guide | ❌ | Similar to integrations/hermes/README.md |
| T14 | Build OAuth 2.1 server for ChatGPT App Store path | 🔒 | BLOCKED: requires significant auth infrastructure. Do after Custom GPT ships. See Architecture note below. |
| T15 | Submit to ChatGPT App Directory | 🔒 | BLOCKED on T14 (OAuth) |

**Architecture note for T14 (OAuth 2.1):**
ChatGPT App Store requires OAuth 2.1 with PKCE. Options:
- Option A: Cloudflare Worker at `auth.mcpwp.net` that fronts all WP sites (like agency proxy pattern). User OAuth-authenticates to mcpwp.net, token maps to their WP site + API key.
- Option B: WordPress plugin implements OAuth 2.1 server directly (add `/.well-known/oauth-authorization-server`, `/oauth/authorize`, `/oauth/token` endpoints to MCPWP). Each site is its own OAuth server.
- **Recommendation: Option A** (central CF Worker) — simpler for users, one review submission, one fixed URL for App Store.

### P2 — WP.org Free Version

**Context:** WP.org requires clean GPL code, no encrypted/obfuscated files, no calls to external services without disclosure, no premium features in the free zip. `docs/FREE_PRO_SPLIT.md` documents the tier split. Previous submission status unknown — need to reapply.

| # | Task | Status | Notes |
|---|------|--------|-------|
| T20 | Audit current free tier against WP.org guidelines | ❌ | Check: no base64 encoded PHP, all external API calls disclosed, no freemium upsells that violate guidelines |
| T21 | Build clean WP.org free zip (strip pro classes, Freemius SDK review) | ❌ | Use `scripts/build-wporg.sh`. Verify output has no pro-only files. |
| T22 | Test free zip on clean WP install | ❌ | Install from zip, activate, generate key, verify free tools work |
| T23 | Update `readme.txt` for WP.org requirements | ❌ | Must have: tested up to, stable tag, description ≤150 chars, screenshots, FAQ, changelog. No marketing language. |
| T24 | Prepare WP.org screenshots (5 max, PNG, 1280×800) | ❌ | Show: setup page, API key, tools list, sample tool call result, control room |
| T25 | Submit to WP.org | ❌ | Needs T20-T24. WP.org review takes 2-8 weeks. Submit early. |
| T26 | Address WP.org review feedback | ❌ | They always have feedback. Assign 1-2 iteration rounds. |

**Known WP.org rejection triggers to fix first:**
- Freemius SDK: must disclose data collection in readme.txt Privacy section (already done in v2.8.45)
- External API calls: PostHog, OpenAI, Gemini, Pexels — all must be in readme.txt under "External Services" section
- No `eval()`, no obfuscation
- Plugin must work without any external account (free tier must be usable standalone)

### P3 — Integration Publishing (after T03/T04 testing passes)

| # | Task | Status | Notes |
|---|------|--------|-------|
| T30 | Publish ClawHub skill (`npm i -g clawhub && clawhub skill publish ./integrations/clawhub`) | ❌ | Needs T03 (header test) to pass first |
| T31 | Publish Hermes Skills Hub skill | ❌ | Needs T04. Command: `hermes skills publish` from integrations/hermes/ |
| T32 | Submit to MCP registries: mcp.so, smithery.ai, glama.ai | ❌ | SEO + discovery. Free submissions. |
| T33 | Submit to Claude Connector directory | 🔒 | BLOCKED on OAuth 2.1 (same blocker as T14). Use Claude Desktop Extension (MCPB) as interim. |
| T34 | Build Claude Desktop Extension (MCPB format) as Claude Connector interim | ❌ | MCPB supports X-API-Key + per-user URL. Submission form: clau.de/desktop-extention-submission |

### P4 — Launch Assets

| # | Task | Status | Notes |
|---|------|--------|-------|
| T40 | Record demo video (90s) | ❌ | Show: connect Claude → run wp_onboard → build page → SEO audit. Screen record + voiceover. |
| T41 | Make Product Hunt gallery images (5x 1270×760) | ❌ | Shots: admin UI, tool list, Elementor build, agency proxy, Chat tab |
| T42 | Update mcpwp.net landing page | ❌ | Reflect v2.8.49 features, add OpenClaw/Hermes/ChatGPT logos, demo video embed |
| T43 | Write Product Hunt maker first comment | ❌ | Pre-write, post at 00:01 PST on launch day |
| T44 | Warm up audience (Twitter teaser thread) | ❌ | Post 1 week before: "Claude controlling WordPress" GIF |
| T45 | DM 10-20 WP agency owners for beta + PH upvote | ❌ | Target: WP agency Twitter/LinkedIn community |
| T46 | Set Product Hunt launch date | ❌ | Tuesday–Thursday, 00:01 PST. Avoid Mon/Fri. |

---

## Backlog (future sprints)

### v2.9 — MCP Primitives

| # | Task | Notes |
|---|------|-------|
| T50 | MCP Resources — expose WP content as browsable resources | Pages/posts/media navigable without calling tools. First WP MCP server with all 3 primitives. |
| T51 | MCP Prompts — reusable editorial + SEO workflow templates | "Write homepage", "audit SEO", "build landing page" as prompt templates |
| T52 | BM25-optimized tool descriptions | Short keyword-rich descriptions on all 120+ tools. Improves Hermes Tool Search + any deferred-tool system. |

### v3.0 — Auth Layer

| # | Task | Notes |
|---|------|-------|
| T60 | OAuth 2.1 central server (CF Worker at auth.mcpwp.net) | Unlocks: ChatGPT App Store, Claude Connector directory. See T14 architecture note. |
| T61 | ChatGPT App Store submission | Needs T60 |
| T62 | Claude Connector directory submission | Needs T60. Submission: clau.de/mcp-directory-submission |

### v3.1 — Intelligence

| # | Task | Notes |
|---|------|-------|
| T70 | Tool Search / deferred tool loading in MCPWP | Serve tools lazily via BM25 bridge (tool_search, tool_describe, tool_call). Any agent with Tool Search support gets 25-50% accuracy gain. |
| T71 | Per-site custom tool registry | Site owners register custom MCP tools without modifying plugin |
| T72 | Multi-agent handoffs | SEO agent + content agent + deploy agent in sequence |

---

## Key Files

| File | Purpose |
|------|---------|
| `BACKLOG.md` | This file — persistent task tracker |
| `docs/PRODUCT_ROADMAP.md` | Strategic direction (older, partially stale) |
| `docs/FREE_PRO_SPLIT.md` | Free vs Pro tool tier reference |
| `docs/openapi-chatgpt.yaml` | ChatGPT OpenAPI schema (stale — needs T10 update) |
| `docs/chatgpt-gpt-instructions.md` | Custom GPT system prompt |
| `integrations/clawhub/SKILL.md` | OpenClaw ClawHub skill |
| `integrations/hermes/SKILL.md` | Hermes Skills Hub skill |
| `integrations/hermes/README.md` | Hermes setup guide |
| `scripts/build-wporg.sh` | WP.org free zip builder |
| `site-pilot-ai/site-pilot-ai.php` | Main plugin file (version constant) |
| `version.json` | Self-update manifest (at repo root) |

---

## Decisions Made

| Decision | Rationale |
|----------|-----------|
| ChatGPT Custom GPT first, App Store later | Custom GPT works with X-API-Key today. App Store needs OAuth 2.1 — bigger build. |
| OAuth 2.1 via central CF Worker (not per-site) | One submission URL for App Store. User experience: one OAuth flow, not per-site. |
| Claude Connector via Desktop Extension first | Remote Connector needs OAuth + fixed URL. MCPB handles per-user URL + API key — ships faster. |
| Don't publish ClawHub/Hermes skills until tested | OpenClaw has header bug #65590. Ship quality, not speed. |
| WP.org free version is a separate build | `scripts/build-wporg.sh` strips pro features. Don't submit the full plugin zip. |

---

## Needs From Hadi

| Item | Context |
|------|---------|
| Confirm launch date window | Product Hunt launch day needs to be set. Suggest picking a specific week. |
| Demo site access | Need a live public WordPress site (not localhost) to demo against for ChatGPT GPT testing and video recording. |
| WP.org submission login | Need WP.org account credentials or confirm Hadi submits manually. |
| ChatGPT account (Plus+) | Need for Custom GPT creation and Developer Mode MCP testing. |
| Product Hunt account | Hadi should be the maker. Need account ready for launch day. |
