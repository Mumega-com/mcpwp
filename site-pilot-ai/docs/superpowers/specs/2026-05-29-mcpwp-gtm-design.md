# MCPWP — Go-To-Market Design
**Date:** 2026-05-29  
**Status:** Active  
**Owner:** Product (Hadi / Mumega)

---

## What We're Building

MCPWP is the MCP operator layer for WordPress. It connects WordPress to AI assistants (Claude, Codex, Cursor, Windsurf) via the Model Context Protocol, adding safety, approvals, and production-grade workflows on top of raw MCP access.

**Positioning:** *The MCP operator layer for WordPress. Connect Claude, Codex, or any MCP client to your WordPress sites — safely, repeatably, with human approval gates.*

---

## Primary Buyer

**WordPress developer / agency.** Builds and manages sites for clients. Uses Claude Code, Cursor, or Codex CLI to do client work faster. Needs multi-site control, archetypes, design references, and agency workflows.

The content team, shop owner, or blogger is the *user* their client becomes — not the buyer.

**Why not target everyone:** The product requires MCP client setup, API key configuration, and understanding of how AI agents connect to a site. That's a developer workflow. Message for developers; others self-select in.

---

## Pricing

| Plan | Price | Sites | What's included |
|------|-------|-------|-----------------|
| **Free** | $0 | 1 | WP.org: core MCP, API keys, posts, pages, media, drafts, menus, basic Elementor, activity log, approval gates |
| **Pro** | $79/year | 1 | All free + SEO integrations, WooCommerce, Elementor Pro, design references, archetypes, agent workflows, search performance, AI providers |
| **Agency** | $249/year | Unlimited | All Pro + agency dashboard, multi-site management, centralized key management |

**Rationale:** Per-site annual licensing is the WordPress developer standard (cf. Gravity Forms $59, Elementor Pro $99). Pro at $79 is below Elementor Pro and above basic utility plugins — right for the category. Agency tier unlocks when the agency dashboard ships.

**Distribution:** Freemius for paid tiers. WP.org for free.

---

## Launch Sequence

### Phase 1 — Stabilize (DONE 2026-05-29)
- [x] Fix GitHub remote URL (mcp-for-wp → mcpwp)
- [x] Resolve 22 Dependabot vulnerabilities (MCP SDK 1.25.3 → 1.29.0)
- [x] Split 7,757-line god class into 4 focused REST controllers
- [x] Merge feature branch into main; clean branch state
- [x] freemius/pro synced to main
- [x] free/wporg updated to v2.8.31, WP.org ZIP built (253 files, clean)

### Phase 2 — WP.org Submission
- [ ] Run Plugin Check on mumega-mcp-2.8.31.zip (target: 0 ERRORs)
- [ ] Verify screenshots are current (4 exist in assets/)
- [ ] Submit to WordPress.org plugin repository
- [ ] Set up SVN assets directory (banners, icons) separately from plugin ZIP

### Phase 3 — Freemius Paid Launch
- [ ] Configure Pro plan ($79/year/site) in Freemius dashboard
- [ ] Verify upgrade path from WP.org free install to Pro
- [ ] Test Freemius trial flow end-to-end
- [ ] Agency plan ($249/year/unlimited) — hold until agency dashboard ships

### Phase 4 — Distribution (after WP.org live)
- [ ] Product Hunt launch (coordinate with WP.org approval date)
- [ ] WP community: r/Wordpress, WP Builds newsletter, Post Status
- [ ] X/Twitter: launch thread with demo GIF showing Claude → MCPWP → site edit
- [ ] Blog post: "How to connect Claude to your WordPress site in 5 minutes"
- [ ] Outreach to MCP communities: Claude users Discord, Cursor Discord

---

## Hero Use Case (website / Product Hunt headline)

> *Tell Claude to update your client's homepage. MCPWP handles the approval, the Elementor edit, and the activity log entry.*

Supporting demos:
1. Claude Code + MCPWP creating a WooCommerce product page from a design reference
2. Approval gate blocking a destructive delete until the human clicks approve
3. SEO audit surfaced in Claude context → autofix plan applied

---

## What Differentiates MCPWP

Other WordPress MCP implementations give raw access. MCPWP adds:
- **Approval gates** — destructive, publish, commerce, and theme-builder mutations require human sign-off
- **Agent playbooks** — deterministic multi-step workflows (not just individual tool calls)
- **Content graph** — site-wide content intelligence for internal linking and SEO
- **Archetypes** — reusable page/product patterns that agents can instantiate
- **Design references** — agents work from approved visual source material, not freeform

This is the difference between "AI can touch my WordPress site" and "AI operates my WordPress site like a production system."

---

## Technical Debt to Address Before Agency Tier

1. **`class-spai-rest-site.php` still 4,506 lines** — onboard method alone is ~260 lines and could be extracted. Acceptable for launch, track for next sprint.
2. **Capability registry not fully implemented** — `CAPABILITY_MAP.md` defines the target; enforcement is still scattered. Centralize before Agency tier ships.
3. **REST namespace `site-pilot-ai/v1`** — visible to API consumers, breaking to change. Leave for v3.0 major bump only.
4. **Internal `SPAI_` prefix** — invisible to users, renaming would break DB options on existing installs. Do not rename.

---

## Success Metrics

| Metric | 30-day target | 90-day target |
|--------|--------------|---------------|
| WP.org active installs | 500 | 2,000 |
| Pro conversions | 10 | 50 |
| Freemius MRR | $60 | $330 |
| GitHub stars | 50 | 150 |
