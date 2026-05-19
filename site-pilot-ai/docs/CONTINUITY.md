# Continuity Notes

This file captures the current state so work can continue after context compaction or handoff.

## Current Branch and PR

- Branch: `codex/wporg-freemius-packaging`
- Draft PR: https://github.com/Mumega-com/mcp-for-wp/pull/257
- Commit in PR: `77554c1`
- Latest docs commit in PR: `4e68822`
- Latest WP.org-free packaging commit: `34b5c1d`

## Current Release Candidate

- Version: `2.8.5`
- WP.org ZIP: `scripts/mumega-mcp-2.8.5.zip`
- Freemius ZIP: `scripts/site-pilot-ai-freemius-2.8.5.zip`
- WP.org Plugin Check baseline: `0 ERROR`, `352 WARNING`.
- WP.org free ZIP contents: 99 files, no Freemius SDK, no Pro modules, no legacy updater.

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
2. Tag `v2.8.5`.
3. Create/execute Sprint 4: free/pro capability split.
4. Create/execute Sprint 5: compact deterministic MCP router.
5. Create/execute Sprint 6: official WordPress MCP/Abilities alignment.

## GitHub Planning

Milestones created:

- Sprint 4 - Free/Pro Split
- Sprint 5 - Compact MCP Router
- Sprint 6 - WordPress MCP Alignment

Issues created:

- #258 Document free/pro capability split
- #259 Create central capability registry
- #260 Gate Pro MCP tools and REST routes consistently
- #261 Gate Pro admin UI and update readme scope
- #262 Add compact MCP tool registry
- #263 Implement deterministic MCP router
- #264 Add MCP mode setting for compact vs legacy
- #265 Assess official WordPress MCP and Abilities integration
- #266 Map SitePilotAI workflows onto WordPress Abilities where practical
- #267 Add design-reference intake workflow
- #268 Define agent-facing workflow handles
- #269 Implement approval gates for agent mutations

## SOS Bus

Manual agent messages should use:

```bash
/home/mumega/.local/bin/sos-mcp-send <agent> "<message>"
```

Do not parse `~/.codex/config.toml` for bearer tokens. The wrapper handles TOML parsing.

Recent bus messages were sent to `mumcp` with findings about PR #257 and the packaging validation.

## Caution

Do not delete legacy MCP tools immediately. Add compact router support first, make it the default for new/WP.org installs, and keep legacy/expanded mode as a compatibility path until usage is understood.

The key product frame to preserve after compaction: SitePilotAI is the WordPress execution layer for external AI agents. Humans approve and audit; agents execute through compact, deterministic tools.
