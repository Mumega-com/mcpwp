# Gap Register

This register captures product and engineering gaps that must stay visible across compaction, handoff, and sprint planning.

## Highest Priority

These unlock safe agent autonomy.

### Approval, Diff, And Rollback Pipeline

Gap: plans mention approval-safe changes, but there is not yet a central mutation pipeline.

Needed:

- Preview before mutation.
- Block/content/meta/template diffs.
- Human approval state.
- Apply step.
- Rollback handle.
- Audit log entry tying actor, API key, tool, diff, affected resources, and rollback.

Relevant issues:

- #269 Implement approval gates for agent mutations.
- #281 Add section-level Gutenberg diff, patch, and rollback.
- #307 Add approval-safe SEO autofix workflows.

Status: first implementation started with a central approval store, approval REST/MCP tools, an approval-required path for `wp_set_blocks`, and approval-first section patches through `wp_patch_block_section`. Current apply/rollback support is limited to Gutenberg post-content updates; next slices must generalize the mutation adapter for meta, menus, options, Elementor, SEO, commerce, and template changes.

### Section-Level Gutenberg Patching

Gap: agents can get/set blocks, parse blocks, and serialize blocks, but still need deterministic section-level edits.

Needed:

- Identify sections by block path, heading, anchor, pattern, or semantic recipe.
- Patch one section without rewriting the full page.
- Validate block-native output.
- Save as a revision or draft before publish.
- Read back and compare.

Relevant issues:

- #281 Add section-level Gutenberg diff, patch, and rollback.
- #279 Enforce block-native Gutenberg guardrails for agent edits.

Status: first implementation adds `wp_patch_block_section` and `POST /blocks/{id}/section`. It can select sections by path, anchor, or heading and creates an approval request by default. Future slices should add richer section inventory, semantic recipes, visual preview, and fine-grained diff output.

### Internal Content Graph

Gap: site context and content inventory exist, but not a graph of nodes, edges, backlinks, orphan pages, hubs, and related content.

Needed:

- Nodes for posts, pages, CPTs, taxonomies, menu items, media, patterns, and template parts.
- Edges for links, menus, parent/child pages, taxonomy membership, embeds, media usage, and suggested relationships.
- Signals for title, slug, headings, excerpt, status, modified date, SEO fields, inbound/outbound link counts, and anchor text.
- Link suggestions with approval-ready diffs.

Relevant issues:

- #283 Add internal content graph for agent link suggestions.
- #282 Add internal link validation to Gutenberg publishing checks.
- #290 Build internal link graph with PageRank-style signals.

Status: first read-only implementation added `wp_get_content_graph` and `GET /site-pilot-ai/v1/content-graph`. It returns content nodes, content-link edges, parent/child edges, inbound/outbound counts, menu presence, headings, anchors, and orphan candidates. Link workflows now include `wp_suggest_internal_links` for graph-based suggestions, `wp_apply_internal_link` for approval-first insertion of existing graph targets, and `wp_validate_internal_links` for read-only validation. Weighted graph signals now include taxonomy edges, menu depth, freshness score, hub score, orphan severity, and PageRank-style rank score. Richer contextual placement is still open.

## Core Architecture Gaps

### Compact MCP Router

Gap: the current MCP tool surface remains large.

Needed:

- Compact actions such as `page.build`, `page.section.update`, `seo.audit`, `links.suggest`, `pattern.create`, and `content.brief`.
- Deterministic dispatch through capability registry.
- Default compact mode for new/WP.org installs.
- Legacy mode retained for compatibility.

Relevant issues:

- #262 Add compact MCP tool registry.
- #263 Implement deterministic MCP router.
- #264 Add MCP mode setting for compact vs legacy.
- #276 Add Gutenberg compact router actions.

### Capability Registry And Free/Pro Gating

Gap: packaging gates exist, but capability rules should be centralized across MCP, REST, admin UI, docs, and build targets.

Needed:

- Single registry for feature, tier, route, MCP tool, admin surface, required scopes, and build eligibility.
- WP.org/Freemius/self-hosted build awareness.
- Tests that prove Pro tools/routes cannot leak into WP.org packages.

Relevant issues:

- #259 Create central capability registry.
- #260 Gate Pro MCP tools and REST routes consistently.
- #261 Gate Pro admin UI and update readme scope.

### Freemius Product Setup

Gap: Freemius code packaging exists, but the actual Freemius product/account setup and upload process is not complete.

Needed:

- Freemius account and product created.
- Product IDs/public keys/secrets added to `includes/freemius-init.php` through a secure release process.
- Plans, pricing, trial policy, and feature gates mapped to the capability registry.
- Freemius ZIP upload and install/upgrade smoke test.
- Customer-facing upgrade copy that stays compliant with WordPress.org rules.
- Release checklist for WP.org free package, Freemius free/pro package, and self-hosted/internal package.

Relevant docs:

- `docs/FREEMIUS_SETUP.md`
- `docs/FREE_PRO_SPLIT.md`
- `docs/PACKAGING.md`

### Native WordPress MCP / Abilities Alignment

Gap: official WordPress MCP/Abilities direction is tracked but not implemented.

Needed:

- Map Mumega workflows onto native abilities where practical.
- Avoid duplicating generic WordPress operations when native support exists.
- Keep Mumega value in higher-level workflows, safety, SEO intelligence, graph, design memory, and approvals.

Relevant issues:

- #265 Assess official WordPress MCP and Abilities integration.
- #266 Map Mumega MCP workflows onto WordPress Abilities where practical.

## WordPress Native Editing Gaps

### Block Safety Validator

Gap: the policy exists, but code enforcement is not complete.

Needed:

- Detect whole-page classic/null blocks.
- Detect `core/html` use as a shortcut.
- Detect inline `<script>` and `<style>`.
- Detect unsafe iframes and opaque embeds.
- Return approval-required errors instead of silently saving.

Relevant issue:

- #279 Enforce block-native Gutenberg guardrails for agent edits.

Status: first implementation added `wp_validate_blocks`, `POST /site-pilot-ai/v1/blocks/validate`, safety reports on block parse/serialize, and default rejection in `wp_set_blocks` unless `allow_restricted_blocks` and `approval_note` are provided. It currently detects whole-page classic content, classic/null blocks, `core/html`, inline script/style tags, and unsafe iframes.

### Patterns, Template Parts, And Global Styles

Gap: agents can list patterns, but cannot safely manage reusable native WordPress design assets.

Needed:

- Create/update reusable patterns.
- Inspect/update template parts with draft and rollback.
- Read theme.json/global styles.
- Respect theme-supported design tokens.

Relevant issues:

- #275 Add Gutenberg pattern and template-part workflows.
- #272 Add Gutenberg design-system discovery endpoint.

### Persistent Design And Content Memory

Gap: site context exists, but structured memory is not enough.

Needed:

- Brand voice.
- Approved layouts.
- Forbidden patterns.
- Reusable sections.
- Target audiences.
- Product/service facts.
- Evidence and claims library.

Relevant issues:

- #267 Add design-reference intake workflow.
- #268 Define agent-facing workflow handles.

## SEO Intelligence Gaps

### SEO Data Model

Gap: SEO plans exist, but normalized storage for issues, crawls, scores, and trends is not implemented.

Needed:

- Issue records with severity, evidence, URL, resource ID, category, recommendation, and auto-fix eligibility.
- Crawl run records.
- New/resolved/persistent issue history.
- Health scoring without ranking promises.

Relevant issues:

- #296 Build SEO issue model and scoring system.
- #303 Add SEO crawl history and trend storage.

### Technical SEO Audit

Gap: current SEO tooling is plugin metadata-focused; Semrush-like technical crawling is not built.

Needed:

- Crawlability and indexability checks.
- Canonical/redirect consistency.
- robots.txt and robots meta.
- Sitemap inclusion.
- Broken links and orphan detection.
- Duplicate title/meta detection.
- Heading/image/schema checks.

Relevant issues:

- #291 Build technical SEO site audit engine.
- #284 Add search and AI crawler visibility audit.
- #287 Add sitemap freshness and IndexNow workflow.
- #288 Add page experience and media SEO checks for Gutenberg pages.

Status: first pre-publish slice adds `wp_validate_seo_readiness` and `GET /site-pilot-ai/v1/seo/readiness/{id}`. It checks title, slug, thin content, H1, heading order, meta description, image alt text, internal links, orphan state, noindex, canonical overrides, robots.txt, sitemap hints, and schema hints without mutating content. Structured data first slice adds `wp_validate_structured_data` and `GET /site-pilot-ai/v1/seo/structured-data/{id}`. Media SEO first slice adds `wp_audit_media_seo` and `GET /site-pilot-ai/v1/seo/media/{id}`. Site audit first slice adds `wp_seo_audit_site` and `GET /site-pilot-ai/v1/seo/audit`.

### Keyword, Topic, And Content Strategy

Gap: no keyword inventory, gap analysis, cannibalization detection, or content brief generator.

Needed:

- Local topic/keyword extraction.
- Search Console/Bing/import data adapters.
- Content gap and refresh opportunities.
- Cannibalization detection.
- Agent-safe briefs.
- Topic cluster and hub planning.

Relevant issues:

- #297 Build keyword and topic inventory from site content.
- #298 Add content gap and opportunity workflow.
- #299 Add keyword cannibalization and duplicate intent detection.
- #300 Add agent-safe SEO content brief generator.
- #301 Add competitor and SERP import interface.
- #302 Add topical cluster and hub page planner.

### Structured Data

Gap: structured data recommendations now have a first read-only slice, but no approved mutation/integration workflow yet.

Needed:

- Inventory existing JSON-LD/microdata.
- Validate visible-content alignment.
- Recommend page-appropriate schema.
- Integrate through SEO plugin APIs where possible.
- Avoid fake or unsupported schema.

Relevant issues:

- #285 Add structured data recommendation and validation workflow.
- #293 Build structured data inventory and validator.

Status: `wp_validate_structured_data` inventories JSON-LD, microdata, and schema.org hints; reports invalid JSON-LD, missing `@context`/`@type`, basic Article/FAQ/Product shape issues, and page-appropriate recommendations. Next slices should integrate SEO plugin schema APIs and approval-safe schema updates.

## Operational Gaps

### Testing

Gap: local manual WordPress validation exists, but repeatable E2E tests are not complete.

Needed:

- Activate plugin in local WP.
- Create Gutenberg page.
- Parse/save/read back blocks.
- Validate block-native guardrails.
- Run SEO checks.
- Suggest internal links.
- Roll back a mutation.
- Verify WP.org package gates.

Relevant issue:

- #274 Add local WordPress Gutenberg route and save tests.

### Performance

Gap: graphs and audits can be expensive.

Needed:

- Batch processing.
- Background jobs.
- Cache invalidation.
- Progress state.
- Per-run limits.
- Site-size-aware defaults.

No dedicated issue yet; create one before implementing graph or audit crawlers.

### Security And Scopes

Gap: more agent power requires stronger controls.

Needed:

- Fine-grained scopes for draft, publish, design, SEO, links, settings, and admin.
- Destructive action approval.
- Better audit log detail.
- Rate limits per action class.
- Role-based tool visibility.

Relevant issues:

- #269 Implement approval gates for agent mutations.
- #263 Implement deterministic MCP router.

### Admin UX

Gap: users need clear screens for agent control and SEO/approval state.

Needed:

- Pending approvals.
- Recent changes.
- Failed actions.
- Rollback actions.
- SEO issue dashboard.
- Next recommended tasks.

Relevant issues:

- #305 Add SEO dashboard and scheduled recommendations.

### Agent Playbooks

Gap: docs explain direction, but concise agent playbooks are needed.

Needed playbooks:

- Build a Gutenberg page.
- Update one section.
- Run an SEO audit.
- Create a content brief.
- Suggest/apply internal links.
- Roll back a change.

Relevant docs:

- `docs/AGENT_WORKFLOWS.md`
- `docs/GUTENBERG_AGENT_DESIGN_SYSTEM.md`
- `docs/SEO_INTELLIGENCE_ROADMAP.md`
