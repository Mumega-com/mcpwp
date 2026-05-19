# Continuity Notes

This file captures the current state so work can continue after context compaction or handoff.

## Current Branch and PR

- Branch: `codex/wporg-freemius-packaging`
- Draft PR: https://github.com/Mumega-com/mcp-for-wp/pull/257
- Commit in PR: `77554c1`
- Latest docs commit in PR: `4e68822`
- Latest WP.org-free packaging commit: `d500952`

## Current Release Candidate

- Version: `2.8.6`
- WP.org ZIP: `scripts/mumega-mcp-2.8.6.zip`
- Freemius ZIP: pending rebuild on `freemius/pro-packaging`.
- WP.org Plugin Check baseline: `0 ERROR`, `352 WARNING`.
- WP.org free ZIP contents: 99 files, no Freemius SDK, no Pro modules, no legacy updater.
- Local WordPress install test: previously passed for the same WP.org package shape; rerun after upload if needed.
- WP.org ZIP SHA256: `c9f9cb051822918f22d228047236b112707765e5ff11700ebf95b1ca9b7d451e`.
- Freemius ZIP SHA256: pending rebuild on `freemius/pro-packaging`.
- GitHub draft release upload: https://github.com/Mumega-com/mcp-for-wp/releases/tag/untagged-6e8bf6009d0eb8c5ddac

## Completed in PR #257

- Text domain normalized to `mumega-mcp`.
- WP.org package root changed to `mumega-mcp/`.
- Canonical site URLs updated to `https://sitepilotai.mumega.com/`.
- Freemius build script added.
- Freemius bootstrap gated behind `SPAI_FREEMIUS_BUILD`.
- Freemius license state recognized by `Spai_License`.
- WP.org build excludes Freemius SDK and updater.
- WP.org build excludes `includes/pro/` and disables Pro MCP exposure through `SPAI_WPORG_BUILD`.
- `site-pilot-ai.php` only loads Pro modules outside WP.org builds when `Spai_License::is_pro()` is true.
- Freemius build includes SDK/bootstrap and excludes legacy updater.
- Planning docs added for free/pro split, packaging, capability map, compact router, continuity, and agent workflows.

## Next Recommended Work

1. Merge PR #257 after review.
2. Upload `scripts/mumega-mcp-2.8.6.zip` to WordPress.org using the account/SVN workflow.
3. Publish or retarget the GitHub draft release after merge.
4. Create/execute Sprint 4: free/pro capability split.
5. Create/execute Sprint 5: compact deterministic MCP router.
6. Create/execute Sprint 6: official WordPress MCP/Abilities alignment.
7. Continue Gutenberg-first work from `feature/gutenberg-agent-design-system`; see `docs/GUTENBERG_AGENT_DESIGN_SYSTEM.md`.

## GitHub Planning

Milestones created:

- Sprint 4 - Free/Pro Split
- Sprint 5 - Compact MCP Router
- Sprint 6 - WordPress MCP Alignment
- Sprint 7 - Gutenberg Agent Design System

Issues created:

- #258 Document free/pro capability split
- #259 Create central capability registry
- #260 Gate Pro MCP tools and REST routes consistently
- #261 Gate Pro admin UI and update readme scope
- #262 Add compact MCP tool registry
- #263 Implement deterministic MCP router
- #264 Add MCP mode setting for compact vs legacy
- #265 Assess official WordPress MCP and Abilities integration
- #266 Map Mumega MCP workflows onto WordPress Abilities where practical
- #267 Add design-reference intake workflow
- #268 Define agent-facing workflow handles
- #269 Implement approval gates for agent mutations
- #272 Add Gutenberg design-system discovery endpoint
- #273 Add Gutenberg block parse and serialize MCP tools
- #274 Add local WordPress Gutenberg route and save tests
- #275 Add Gutenberg pattern and template-part workflows
- #276 Add Gutenberg compact router actions
- #278 Add SEO-safe Gutenberg publishing workflow
- #279 Enforce block-native Gutenberg guardrails for agent edits
- #281 Add section-level Gutenberg diff, patch, and rollback
- #282 Add internal link validation to Gutenberg publishing checks
- #283 Add internal content graph for agent link suggestions
- #284 Add search and AI crawler visibility audit
- #285 Add structured data recommendation and validation workflow
- #286 Add content quality and entity coverage audit for AI search
- #287 Add sitemap freshness and IndexNow workflow
- #288 Add page experience and media SEO checks for Gutenberg pages
- #289 Add Search Console and webmaster verification notes

## SOS Bus

Manual agent messages should use:

```bash
/home/mumega/.local/bin/sos-mcp-send <agent> "<message>"
```

Do not parse `~/.codex/config.toml` for bearer tokens. The wrapper handles TOML parsing.

Recent bus messages were sent to `mumcp` with findings about PR #257 and the packaging validation.

Branding note: public product/display name is now `Mumega MCP`. Keep the WP.org slug/text domain as `mumega-mcp`; keep legacy route/package identifiers such as `site-pilot-ai/v1` unless a migration is planned.

## Caution

Do not delete legacy MCP tools immediately. Add compact router support first, make it the default for new/WP.org installs, and keep legacy/expanded mode as a compatibility path until usage is understood.

The key product frame to preserve after compaction: Mumega MCP is the WordPress execution layer for external AI agents. Humans approve and audit; agents execute through compact, deterministic tools.

Gutenberg direction: make native WordPress blocks the default free build surface for agents. Agents should treat Gutenberg like a structured HTML DOM: discover the design system, generate block markup, parse before saving, save through MCP/REST, then read back the block tree. Elementor remains supported for existing sites, but Gutenberg is the product's cleanest native WordPress path.

Important product guardrail: agents should not save arbitrary raw HTML, inline JavaScript, or whole-page classic/null blocks as the default path. They should produce editable Gutenberg blocks, request approval for exceptions, and run SEO checks before publishing or updating important pages.

Internal graph gap: the plugin currently has site context, content inventory, search/fetch, and SEO plugin detection, but not a true content/link graph. Sprint 7 now tracks `wp_get_content_graph`, internal link suggestions, orphan detection, broken-link validation, and approval-ready link diffs.

Search/AI discovery direction: pre-publish checks should cover crawlability, indexability, canonicals, sitemap freshness, structured data, internal links, page experience, image/media SEO, and AI crawler access such as OpenAI `OAI-SearchBot`. The goal is not to game search engines; it is to make useful WordPress content easy to crawl, understand, cite, and connect.
