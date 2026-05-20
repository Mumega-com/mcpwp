# Continuity Notes

This file captures the current state so work can continue after context compaction or handoff.

## Current Branch and PR

- Branch: `feature/gutenberg-agent-design-system`
- Draft PR: https://github.com/Mumega-com/mcp-for-wp/pull/257
- Latest working commit in PR: this commit.
- Latest docs update in PR: this commit.
- Latest WP.org-free packaging update: this commit.

## Current Release Candidate

- Version: `2.8.27`
- WP.org ZIP: `scripts/mumega-mcp-2.8.27.zip`.
- Freemius ZIP: pending rebuild on `freemius/pro-packaging`.
- WP.org Plugin Check baseline: `0 ERROR` on packaged `mumega-mcp` ZIP for `2.8.27`.
- WP.org free ZIP contents: 107 files, no Freemius SDK, no Pro modules, no legacy updater.
- Local WordPress approval/apply/rollback smoke test: passed on version `2.8.8`; section patch smoke passed on version `2.8.9`; internal link suggestion smoke passed on version `2.8.10`; internal link application smoke passed on version `2.8.11`; internal link validation smoke passed on version `2.8.12`; weighted content graph smoke passed on version `2.8.13`; SEO readiness smoke passed on version `2.8.14`; structured data smoke passed on version `2.8.15`; combined E2E and media SEO smoke passed on version `2.8.16`; site SEO audit smoke passed on version `2.8.17`; content quality smoke passed on version `2.8.18`; stored SEO issue smoke passed on version `2.8.19`; control room smoke passed on version `2.8.20`; state visual smoke passed on version `2.8.21`; Control Room action smoke passed on version `2.8.22`; event store and REST event smoke passed on version `2.8.23`; site-state snapshot smoke passed on version `2.8.24`; Control Room event inbox smoke passed on version `2.8.25`; agent playbook contract smoke passed on version `2.8.26`; content coherence report smoke passed on version `2.8.27`.
- WP.org ZIP SHA256: `5b2bc0a7649bc83cb3b238d58c2981bfa805e9eef4dfb3a0dcdb9d134d9d9018`.
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
2. Upload the rebuilt WP.org ZIP to WordPress.org using the account/SVN workflow.
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
- Sprint 8 - SEO Intelligence Foundation
- Sprint 9 - Keyword and Content Strategy
- Sprint 10 - SEO Monitoring and Reporting

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
- #290 Build internal link graph with PageRank-style signals
- #291 Build technical SEO site audit engine
- #293 Build structured data inventory and validator
- #294 Build page experience and media audit engine
- #296 Build SEO issue model and scoring system
- #297 Build keyword and topic inventory from site content
- #298 Add content gap and opportunity workflow
- #299 Add keyword cannibalization and duplicate intent detection
- #300 Add agent-safe SEO content brief generator
- #301 Add competitor and SERP import interface
- #302 Add topical cluster and hub page planner
- #303 Add SEO crawl history and trend storage
- #304 Add Search Console/Bing data import and reporting hooks
- #305 Add SEO dashboard and scheduled recommendations
- #306 Add rank tracking placeholder and third-party import abstraction
- #307 Add approval-safe SEO autofix workflows
- #308 Add AI search visibility and citation readiness report
- #309 Build human control room for approvals and SEO issues
- #310 Add Apify trend and SERP import provider
- #311 Add WooCommerce SEO intelligence workflows
- #312 Add AI-first event hooks and outbound webhooks
- #313 Add coherent site state snapshot for agents and Control Room
- #314 Add Control Room event inbox and escalation rules
- #315 Define deterministic agent playbook contracts
- #316 Add content coherence scoring and recommendations

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

Deep SEO roadmap lives in `docs/SEO_INTELLIGENCE_ROADMAP.md`. Product direction: Semrush-like breadth, WordPress-native execution, no ranking promises, imports/provider abstractions instead of default SERP scraping, and approval-safe diffs for every mutating fix.

Gap register lives in `docs/GAP_REGISTER.md`. Highest-priority gaps before serious agent autonomy: approval/diff/rollback pipeline, section-level Gutenberg patching, internal content graph, compact router, capability registry, block safety validator, SEO data model, repeatable local WP E2E tests, performance controls for audits/graphs, and admin UX for approvals/rollback.

Human control room progress: #309 now has the first admin implementation. The Control Room combines pending approvals, approved changes ready to apply, rollback-ready changes, stored SEO issues, filters, recommended next actions, recent agent activity, and a stored SEO audit action so humans can supervise agent work without reading raw MCP responses.

SEO provider backlog: #304 covers Search Console/Bing imports, #297 covers keyword/topic inventory, #310 covers optional Apify trend/SERP provider imports, and #311 covers WooCommerce SEO intelligence. Keep the sequencing SEO-first: core issue model and control room, then keyword/search imports, then WooCommerce as a revenue-focused vertical, then Apify as an optional Pro/provider evidence source.

AI-first hooks backlog: #312 tracks harness-neutral WordPress hooks plus signed outbound webhooks. First slice should emit approval lifecycle and stored SEO audit events, then broaden to SEO issue, content graph, activity, and Control Room alert events. Outbound notifications are safe first; inbound mutating commands must be scoped, signed, logged, and approval-first.

AI-first hooks progress: first slice adds `Spai_Event_Store`, recent normalized event history, `spai_event_emitted`, specific hooks such as `spai_approval_created` and `spai_seo_audit_completed`, webhook forwarding through existing signed webhooks, REST `/events/schema`, REST `/events`, MCP `wp_get_event_schema`, and MCP `wp_list_mcp_events`.

Coherent content-system frame: treat WordPress as one connected state model across posts, pages, Gutenberg blocks, media, taxonomies, menus, internal links, SEO metadata, approvals, activity, commerce, and templates. Agents should respond to current site state and graph relationships instead of seeing disconnected tools or pushing raw page edits.

Site-state snapshot progress: #313 first slice adds `Spai_Site_State`, REST `GET /site-pilot-ai/v1/site-state`, and MCP `wp_get_site_state`. The snapshot summarizes site identity, context configuration, content counts, graph health, approval queues, stored SEO issues, recent normalized events, capability flags, and recommended next actions so agents can start from one coherent state model.

Control Room event inbox progress: #314 first slice adds a normalized Event Inbox panel to the Control Room with event type and risk filters. Escalation rules mark high-risk events, failing SEO audit events, and approval lifecycle events for human attention.

Deterministic playbook progress: #315 first slice adds `Spai_Agent_Playbooks`, REST `GET /site-pilot-ai/v1/agent-playbooks`, and MCP `wp_get_agent_playbook`. Current contracts cover `build_gutenberg_page`, `update_gutenberg_section`, `seo_audit_triage`, `internal_link_improvement`, and `rollback_change` with required tools, validation gates, approval gates, rollback paths, and stop conditions.

Content coherence progress: #316 first slice adds `Spai_Content_Coherence`, REST `GET /site-pilot-ai/v1/content-coherence`, and MCP `wp_get_content_coherence_report`. The score uses site-state inputs across context, graph connection, content depth/freshness, stored SEO issues, approval risk, and event risk, then maps recommendations to deterministic playbooks.

Coming sprint sequence: #312 event hooks, #313 site-state snapshot, #314 Control Room event inbox, #315 deterministic playbooks, #316 content coherence score, then #307 SEO autofix, #304 Search Console/Bing imports, and #311 WooCommerce SEO intelligence. The sequence makes the system observable before it becomes more autonomous.

Implementation progress on PR #277: block safety first slice now exposes `wp_validate_blocks` and `POST /site-pilot-ai/v1/blocks/validate`, adds safety reports to parse/serialize responses, and makes `wp_set_blocks` reject classic HTML, `core/html`, inline script/style tags, and unsafe iframes by default unless an explicit approval note is supplied. Internal graph first slice now exposes `wp_get_content_graph` and `GET /site-pilot-ai/v1/content-graph` with nodes, content links, parent/child edges, inbound/outbound counts, anchors, headings, menu presence, and orphan candidates.

Approval pipeline progress: first slice adds a central approval request store plus `wp_list_approvals`, `wp_get_approval`, `wp_approve_request`, `wp_reject_request`, `wp_apply_approval`, and `wp_rollback_approval`. `wp_set_blocks` can now pass `approval_required=true` to create a pending approval instead of saving immediately. Apply/rollback currently supports Gutenberg post-content updates; future slices must add mutation adapters for section patches, meta, menus, options, Elementor, SEO, commerce, and templates.

Section patching progress: `wp_patch_block_section` and `POST /site-pilot-ai/v1/blocks/{id}/section` now replace one selected Gutenberg section by path, anchor, or heading. The endpoint validates replacement markup and creates an approval request by default; immediate saves require `approval_required=false`.

Internal graph progress: `wp_suggest_internal_links` and `GET /site-pilot-ai/v1/content-graph/suggestions` now return read-only internal link suggestions from the content graph. Suggestions use existing graph URLs only, include a conservative anchor and approval diff, and do not mutate content. `wp_apply_internal_link` and `POST /site-pilot-ai/v1/content-graph/apply-link` now apply accepted graph targets by creating an approval request by default and appending a native Gutenberg related-link paragraph.

Internal link validation progress: `wp_validate_internal_links` and `GET /site-pilot-ai/v1/content-graph/validate-links` now report self-links, duplicate internal targets, empty/weak anchors, missing targets, unpublished targets, and non-canonical URLs without mutating content.

Weighted graph progress: `wp_get_content_graph` now includes `shared_taxonomy` edges plus node-level `menu_depth`, `freshness_days`, `freshness_score`, `hub_score`, `orphan_severity`, and PageRank-style `rank_score` signals for SEO and internal linking decisions.

SEO readiness progress: `wp_validate_seo_readiness` and `GET /site-pilot-ai/v1/seo/readiness/{id}` now provide a read-only pre-publish check for title, slug, content depth, H1, heading order, meta description, image alt text, internal links, orphan state, noindex, canonical override, robots.txt, sitemap hint, and schema hint.

Structured data progress: `wp_validate_structured_data` and `GET /site-pilot-ai/v1/seo/structured-data/{id}` now inventory JSON-LD, microdata, and schema.org hints; report invalid JSON-LD, missing `@context`/`@type`, basic Article/FAQ/Product shape issues, and page-appropriate schema recommendations without mutating content.

Media SEO progress: `wp_audit_media_seo` and `GET /site-pilot-ai/v1/seo/media/{id}` now inspect featured images and content images, reporting missing alt text, generic filenames, large local files, missing dimensions, missing lazy-loading hints, duplicate image reuse, and external image counts without mutating media.

Site SEO audit progress: `wp_seo_audit_site` and `GET /site-pilot-ai/v1/seo/audit` now aggregate readiness, structured data, and media SEO issues across recent posts/pages into prioritized URL rows, category counts, and top issue codes without mutating content.

Content quality progress: `wp_audit_content_quality` and `GET /site-pilot-ai/v1/seo/content-quality/{id}` now report word depth, summary intro, question coverage, entity-like names, freshness, trust signals, and external reference hints for AI-search/citation readiness without mutating content. `wp_seo_audit_site` includes this category in aggregate counts.

Stored SEO issue progress: `wp_seo_audit_site` and `GET /site-pilot-ai/v1/seo/audit` now accept `store=true` to persist recent audit runs and normalized top issue records. `wp_get_seo_issues` and `GET /site-pilot-ai/v1/seo/issues` list issues by status, severity, category, post ID, run ID, and limit. Resolution is scoped to posts included in the current run so partial audits do not incorrectly close issues from skipped content.
