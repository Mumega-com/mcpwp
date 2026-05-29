# MCPWP — Go-To-Market Design
**Date:** 2026-05-29  
**Status:** Active — updated after full session  
**Owner:** Product (Hadi / Mumega)

---

## What We're Building

MCPWP is the MCP operator layer for WordPress. It connects WordPress to AI assistants (Claude, Codex, Cursor, Windsurf, Hermes, OpenClaw) via the Model Context Protocol, adding safety, approvals, production-grade workflows, and extensibility on top of raw MCP access.

**Positioning:** *The MCP operator layer for WordPress. Connect Claude, Codex, or any MCP client to your WordPress sites — safely, repeatably, with human approval gates.*

**v3 positioning adds:** *The extensible WordPress MCP platform. Register your own tools. Pull in external data. Get alerts in Telegram. Let AI operate your store.*

---

## Primary Buyers

**1. WordPress developer / agency (primary)** — Builds and manages sites for clients. Uses Claude Code, Cursor, or Codex CLI to do client work faster. Pays for multi-site, archetypes, design references, agency workflows.

**2. WooCommerce store owner (emerging, v3)** — Runs their own store. Wants AI to monitor stock, fix SEO, handle approvals from Telegram. Lower technical bar once setup is simplified. The hero use case: *"Your store's SEO dropped. Claude found 12 product pages missing meta descriptions. Reply 'fix it' in Telegram to approve."*

Content teams, SaaS teams are secondary — they self-select in.

---

## Pricing

| Plan | Price | Sites | What's included |
|------|-------|-------|-----------------|
| **Free** | $0 | 1 | WP.org: core MCP, API keys, posts, pages, media, drafts, menus, basic Elementor, activity log, approval gates |
| **Pro** | $79/year | 1 | All free + SEO integrations, WooCommerce, Elementor Pro, design references, archetypes, agent workflows, search performance, AI providers, Telegram channel, browser navigation |
| **Agency** | $249/year | Unlimited | All Pro + agency dashboard, multi-site management, centralized key management |

Agency tier holds until agency dashboard ships.

---

## Distribution Channels (Full Picture)

### WordPress ecosystem
- **WP.org** — free tier listing, primary discovery for site owners
- **Freemius** — paid tier checkout, upgrade path from WP.org install

### MCP ecosystem (all need the npm package first)
| Channel | How | Priority |
|---------|-----|----------|
| **npm** (`mcpwp`) | Publish, rename from `site-pilot-ai` | Immediate |
| **Claude Desktop Extension** (`.mcpb`) | Package + submit to Anthropic directory | High |
| **Claude Code community plugins** | `platform.claude.com/plugins/submit` | High |
| **registry.modelcontextprotocol.io** | PR to official MCP registry | High |
| **mcp.so** | GitHub issue | Medium |
| **smithery.ai / glama.ai / lobehub** | Submission forms | Medium |
| **Cline MCP Marketplace** | GitHub PR | Medium |
| **OpenClaw plugin** | npm wrapper + register in openclaw.json | Medium |
| **Hermes skill** | `SKILL.md` in hermes-agent style | Low |
| **mcp-submit** | CLI tool to automate 10+ registry submissions | Use this |

---

## Launch Sequence

### Phase 1 — Stabilize ✅ DONE (2026-05-29)
- [x] Fix GitHub remote URL → `Mumega-com/mcpwp`
- [x] Resolve 22 Dependabot vulnerabilities (MCP SDK 1.25.3 → 1.29.0)
- [x] Split 7,757-line god class into 4 focused REST controllers
- [x] Merge feature branch into main; clean branch state
- [x] `freemius/pro` and `free/wporg` synced to main
- [x] `free/wporg` updated to v2.8.31, WP.org ZIP built clean (253 files)
- [x] GTM and v3 design docs committed

### Phase 2 — npm + Registry submissions (next sprint)
- [ ] Rename npm package `site-pilot-ai` → `mcpwp`, publish
- [ ] Build `.mcpb` Desktop Extension with `manifest.json`
- [ ] Run `mcp-submit` CLI across all major registries
- [ ] Submit to Claude Code community plugins
- [ ] Submit to official MCP registry (PR)

### Phase 3 — WP.org Submission
- [ ] Run Plugin Check on `mumega-mcp-2.8.31.zip` (target: 0 ERRORs)
- [ ] Verify screenshots are current
- [ ] Submit to WordPress.org
- [ ] Set up SVN assets directory

### Phase 4 — Freemius Paid Launch
- [ ] Configure Pro plan ($79/year/site) in Freemius dashboard
- [ ] Test trial + upgrade flow end-to-end
- [ ] Agency plan holds until agency dashboard ships

### Phase 5 — v3 Build (see v3 design spec)
- [ ] Module A: Core scaffold (new slug, options prefix)
- [ ] Module B: PHP-native MCP server (you + PM)
- [ ] Module C: Tool registry + built-in tools (agents)
- [ ] Module D: Dynamic tool loader (you + PM)
- [ ] Module E: Custom tools platform (agents)
- [ ] Module F: WordPress Abilities bridge (agents)
- [ ] Module G: npm proxy rename + publish (agents)
- [ ] Module H: `.mcpb` bundle (agents)

### Phase 6 — Distribution
- [ ] Product Hunt (coordinate with WP.org approval date)
- [ ] WP community: r/Wordpress, WP Builds, Post Status
- [ ] X/Twitter launch thread with demo GIF
- [ ] Blog post: "Connect Claude to your WordPress site in 5 minutes"
- [ ] WooCommerce-specific content: "Let AI run your store"

---

## Hero Use Cases

**For developers:**
> *Tell Claude to update your client's homepage. MCPWP handles the approval, the Elementor edit, and the activity log entry.*

**For WooCommerce store owners (v3):**
> *Your store's SEO dropped. Claude found 12 product pages missing meta descriptions. Reply 'fix it' in Telegram to approve.*

**For agencies:**
> *Connect all your client sites to one dashboard. Let Claude audit, fix, and report across all of them.*

---

## What Differentiates MCPWP

| Feature | MCPWP | Other WP MCP plugins |
|---------|-------|---------------------|
| Approval gates | ✅ | ❌ |
| Agent playbooks | ✅ | ❌ |
| Content graph + SEO intelligence | ✅ | ❌ |
| Archetypes | ✅ | ❌ |
| Design references (Figma) | ✅ | ❌ |
| Custom tools platform | ✅ v3 | ❌ |
| Telegram alerts | ✅ v3 | ❌ |
| Browser navigation | ✅ v3 | Some |
| Dynamic tool loading | ✅ v3 | ❌ |
| WordPress Abilities bridge | ✅ v3 | Partial |

---

## Technical Debt (tracked)
1. `class-spai-rest-site.php` still 4,506 lines — further split possible, non-blocking
2. Capability registry scattered — centralize before Agency tier
3. REST namespace `site-pilot-ai/v1` — leave for v3 major bump
4. Internal `SPAI_` prefix — do not rename (breaks DB options on existing installs)

---

## Success Metrics

| Metric | 30-day | 90-day |
|--------|--------|--------|
| WP.org active installs | 500 | 2,000 |
| npm weekly downloads | 200 | 1,000 |
| Pro conversions | 10 | 50 |
| Freemius MRR | $60 | $330 |
| GitHub stars | 50 | 150 |
