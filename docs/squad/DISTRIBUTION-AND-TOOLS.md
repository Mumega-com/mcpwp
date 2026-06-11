# Distribution Channels + Tools Inventory (2026-06-11)

> Where MCPWP lands, ranked by effort/reward, + the tools the squad needs. Scout research, sourced.
> Feeds the GTM runbook's launch phase (D). US-market first.

## A. The minimal high-leverage pipeline (one focused afternoon)

No single tool submits to all MCP registries — but this pipeline covers the four that matter:

```
npm publish (the node proxy)  →  mcp-publisher publish  →  glama.ai form  →  mcp.so form
       →  pulsemcp Submit  →  PR to punkpeye/awesome-mcp-servers (needs Glama listing first)
```

- **`mcp-publisher` is the one command that matters most** — publishes to the Official MCP Registry (`registry.modelcontextprotocol.io`, which Claude Desktop queries), and **PulseMCP auto-ingests from it weekly.** Install via brew; `init → login github → publish`. Wire into GitHub Actions (OIDC) for CI/CD.

## B. Channels ranked — do these first

| # | Channel | Reach | Effort | Key requirement | Note |
|---|---------|-------|--------|-----------------|------|
| 1 | **Official MCP Registry** | Claude Desktop queries it; downstream auto-ingest | Low | npm artifact + server.json + mcp-publisher | preview (watch breakage) |
| 2 | **WordPress.org** | 60M+ installs | Med | GPL, Plugin Check, AI-API consent disclosure in readme | 1–10 day review; WP.org MCP server lets you submit via Claude |
| 3 | **Glama.ai** | 34k servers, enterprise | Low | web form; **every tool description scored (worst = 40% weight)** | write tool descriptions carefully (ties to #444 BM25 work) |
| 4 | **mcp.so + PulseMCP** | 20k / 11k; PulseMCP newsletter = high-signal | Low | GitHub URL + desc | 10-min each, same day as Glama |
| 5 | **punkpeye/awesome-mcp-servers** | 83k stars, DoFollow backlink | Low | PR (needs Glama listing); mark 🤖🤖🤖 for fast-track | permanent once merged |
| 6 | **Smithery.ai** | 7.3k, one-click install UI | Low-Med | add smithery.yaml to repo for ranking | agent-framework audience = our deployers |
| 7 | **Claude Connector Directory** | Claude.ai millions | **High** | **OAuth 2.1 + Streamable HTTP** + privacy + screenshots + split read/write tools | BLOCKED: we use API-key auth. Engineering prereq = the OAuth keystone (v2.9/M2). Highest-reach AI channel. |
| 8 | **Product Hunt** | top-10: 2–8k/48h; #1: 5–30k | High (6-wk prep) | hunter or 30-day account, assets, maker comment | Tue/Wed 12:01 PT; engagement > raw upvotes |
| 9 | **Hacker News (Show HN)** | front page 500k+ | Low | "Show HN: MCP server that lets AI operate a WordPress site" | MCP×WP novel to HN; be responsive in comments |
| 10 | **WP newsletters/podcasts** | Post Status, The Repository, WP Tavern, WP Builds, WP Minute | Low-Med | pitch as news (MCP+WP genuinely novel) | the agency buyer reads these |

Also: Cursor directory (cursor.directory/plugins/new), Windsurf marketplace, mcp.directory, dev.to/Hashnode tutorials, Reddit (r/ClaudeAI 911k, r/WordPress 700k — **no direct promo, 90/10 rule, demos not announcements**), CodeCanyon (saturated, 37.5% fee, high review burden — skip for now).

## C. GitHub-specific moves (cheap, high-leverage)

1. **Releases as a download channel** — tag v3.x, attach `mcpwp.x.y.z.zip`; GitHub Action `wordpress-org-plugin-deploy` auto-pushes to WP.org SVN on tag. (Ties to #514 — releases stalled at 2.8.31.)
2. **Topics** ✓ already set (mcp, wordpress-plugin, elementor…). Topic pages are Google-indexed + awesome-list prerequisite.
3. **GitHub Sponsors** — 0% fee; tiers ($5 individual / $25 agency / $100 partner); the badge signals project health. (#514 funding gap.)
4. **awesome-lists** — PR into punkpeye + wong2 + TensorBlock + awesome-wordpress. DoFollow backlinks compound SEO + GitHub search rank.
5. **GitHub Discussions over Discord (for now)** — devs already have GH accounts; Q&A is Google-indexed (Discord isn't). Enable Q&A, pin "How to connect your AI agent." Migrate to Discord (#421) when community justifies it.

## D. 2026 positioning flags

- **WordPress 7.0 shipped an AI Client + Connectors API** (Mar 2026). MCPWP should surface as Connectors-compatible — a positioning edge vs older AI plugins. (Candidate issue.)
- **WP.org AI-plugin rejections** focus on "executable code from third-party systems." MCPWP's architecture (AI operates WP; WP doesn't execute AI-generated code) is fine — but readme must spell out the data flow + third-party services. Mind this in the WP.org submission.
- **Official MCP Registry is preview** — possible data resets before GA; monitor.

## E. Tools inventory — have / need / ask-kasra

| Tool | Purpose | Status |
|------|---------|--------|
| PostHog | funnel + tool analytics + LLM obs | **HAVE** (wired in plugin) |
| Freemius + connected Stripe | checkout, license, tax, affiliates | **HAVE** (#512) |
| GHL + its MCP (~21 tools) | funnel/CRM/social/booking arm | **HAVE** (paid) |
| Remotion (digidinc/media) | launch video + social slides | **HAVE** (reuse) |
| GitHub CLI + Actions | issues, releases, CI/CD distribution | **HAVE** |
| Node proxy (npm) | the artifact mcp-publisher needs | HAVE (240-line proxy) — needs npm publish |
| `mcp-publisher` CLI | Official Registry submit | **NEED** (brew install, one-time) |
| Apify (MCP) + Serper + Firecrawl | signal-gathering stack (~$234/mo) | **NEED** (v3.2 signal tools) |
| Social-posting surface | distribute approved content | **NEED** (decide: GHL planner vs Metricool/Buffer) |
| Telegram adapter | the approval gate | **NEED** (reconnect — dropped this session) |
| Test rig (#507) | e2e before any release | **NEED** (tonight's build) |
| OAuth 2.1 + DCR | unblocks Claude Connector Directory + ChatGPT Business | **NEED** (M2 keystone, v2.9-class engineering) |
| Sandboxed mupot | isolated pot for the gated-loop product | **ASK KASRA** (don't self-provision) |

## F. Sequenced into the launch

- **v3.1 launch week:** mcp-publisher + Glama + mcp.so + PulseMCP + punkpeye (the afternoon pipeline) + WP.org submission + Show HN + PH + GitHub releases/sponsors/discussions (#514).
- **Post-launch (v3.2+):** Smithery yaml, Cursor/Windsurf, WP newsletter pitches, signal-stack tools, the OAuth keystone → Claude Connector Directory + ChatGPT Business (the two highest-reach AI channels, both OAuth-gated).
