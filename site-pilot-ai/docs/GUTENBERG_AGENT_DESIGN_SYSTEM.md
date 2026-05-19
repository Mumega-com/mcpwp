# Gutenberg Agent Design System

## Product Direction

Mumega MCP should make Gutenberg feel like a structured page DOM for agents. The user is not a person dragging blocks; the primary user is an external coding/design agent such as Claude Code, Codex CLI/Desktop, OpenClaw, Hermes, Claude Desktop, or another MCP client.

The agent should be able to:

- Discover the site's active theme, block support, block types, and patterns.
- Build pages with native block grammar instead of opaque HTML blobs.
- Parse generated block markup before saving.
- Serialize structured block arrays when the agent wants exact block trees.
- Save and read back the page to confirm WordPress accepted the block tree.
- Prefer native Gutenberg, patterns, template parts, and global styles before third-party builders.
- Pass SEO and editability checks before publish/update.
- See and use the site's internal content graph before adding links or creating new pages.

## Current First Slice

Branch: `feature/gutenberg-agent-design-system`

New free-core MCP tools:

- `wp_get_block_design_system`
- `wp_parse_blocks`
- `wp_serialize_blocks`

New REST endpoints:

- `GET /site-pilot-ai/v1/blocks/design-system`
- `POST /site-pilot-ai/v1/blocks/parse`
- `POST /site-pilot-ai/v1/blocks/serialize`

Existing REST/MCP tools remain:

- `wp_get_blocks`
- `wp_set_blocks`
- `wp_list_block_types`
- `wp_list_block_patterns`

## Agent Workflow

1. Call `wp_get_block_design_system`.
2. Use `patterns`, `recommended_primitives`, and `recipes` to choose the page structure.
3. Generate WordPress block markup, not plain HTML.
4. Call `wp_parse_blocks` and inspect:
   - `has_block_markup`
   - `block_count`
   - block names and nesting
5. Call `wp_set_blocks` with `content` for exact markup or `blocks` for structured save.
6. Call `wp_get_blocks` to confirm the stored page state.
7. Run SEO/editability checks before publish: H1, heading order, slug, title, meta description, image alt text, internal links, and indexability.
8. Use the internal content graph to suggest and apply relevant internal links with an approval-ready diff.

## HTML-Like Mapping

Agents can think in semantic HTML, then emit Gutenberg blocks:

- `section` -> `core/group` with `tagName: "section"`
- `div.columns` -> `core/columns` plus nested `core/column`
- `h1`/`h2` -> `core/heading`
- `p` -> `core/paragraph`
- `a.button` -> `core/buttons` plus nested `core/button`
- `figure/img` -> `core/image`
- `ul/li` -> `core/list`
- spacing -> `core/spacer` or block spacing supports

Plain HTML should be treated as a fallback, not the primary output. If `parse_blocks()` returns a classic/null block for a whole page, the agent produced content WordPress cannot manage as native blocks.

## Block-Native Policy

Default rule: agents must produce editable Gutenberg blocks, not opaque HTML/JS payloads.

Allowed by default:

- Core blocks and registered third-party blocks discovered through `wp_list_block_types`.
- Block patterns discovered through `wp_list_block_patterns`.
- Template parts and reusable patterns after the workflow has approval and rollback support.
- Semantic block attributes, classes, spacing, layout, typography, and theme-supported style controls.

Restricted by default:

- Whole-page classic/null blocks produced by plain HTML.
- `core/html` as a page-building shortcut.
- Inline `<script>` or `<style>` tags inside content.
- Arbitrary iframes or embeds not represented by a supported block.
- Large duplicated sections that should be reusable patterns or template parts.

If an exception is needed, the agent should return a reason and request approval before saving.

## SEO Policy

Gutenberg page creation is not done until the content is publishable and discoverable.

Minimum checks:

- One clear H1.
- Heading levels are ordered and not used only for visual size.
- SEO title and meta description are present where native WP or an SEO plugin supports them.
- Slug is readable and aligned with search intent.
- Excerpt is set for posts and indexable content.
- Images have useful alt text.
- Buttons and links use descriptive text.
- Internal links connect the page to relevant site content.
- New pages are not orphaned and should be connected from at least one relevant hub, menu, archive, or related page where appropriate.
- Canonical/indexing state is not accidentally changed.
- Schema suggestions are generated where appropriate, but not injected blindly.

Native WordPress comes first. If Yoast, Rank Math, SEOPress, or another SEO plugin is detected, the workflow should use the plugin's supported fields and APIs instead of writing random post meta.

## Search And AI Discovery Roadmap

Goal: make each generated page easy for people, Google, Bing, ChatGPT search, and other AI answer engines to crawl, understand, cite, and connect to the rest of the site.

For the deeper Semrush-style roadmap, see `docs/SEO_INTELLIGENCE_ROADMAP.md`.

Current primary-source guidance points to these foundations:

- Helpful, reliable, people-first content remains the core ranking input.
- Google discovers new pages heavily through links, so internal links and crawlable anchors matter.
- Links should be real `<a href="...">` links with descriptive anchor text.
- Structured data helps search systems understand page meaning and can enable rich results, but it must match visible content and follow feature-specific guidelines.
- Robots meta, `noindex`, `nofollow`, `nosnippet`, and `max-snippet` affect both classic search and AI surfaces such as AI Overviews/AI Mode.
- Canonicals, redirects, and sitemap inclusion help search engines choose the preferred URL.
- Page experience still matters: mobile usability, HTTPS, no intrusive interstitials, and good Core Web Vitals.
- For ChatGPT search, allow `OAI-SearchBot` when the site wants to appear in ChatGPT search results; manage `GPTBot` separately if training access policy differs.
- For Bing/IndexNow-supported engines, IndexNow can notify changed URLs, but it does not guarantee indexing and should not be spammed.

Backlog capabilities:

- Search/AI crawler visibility audit: robots.txt, robots meta, canonical, sitemap, `llms.txt`, OAI-SearchBot/GPTBot, and accidental snippet restrictions.
- Structured data recommendation/validation: Article, BreadcrumbList, Product, FAQ, Organization, LocalBusiness, WebSite/SearchAction, SoftwareApplication, and other page-appropriate types.
- Content quality and entity coverage audit: intent, summary, definitions, FAQs, entity names, trust signals, freshness, useful references, and answerability.
- Internal content graph and internal link suggestions.
- Sitemap freshness and IndexNow workflow for changed URLs.
- Page experience and media SEO checks before publish.
- Search Console/Bing Webmaster status notes or integrations where credentials/export data are available.

Agents should treat these checks as a pre-publish gate. The plugin should return a clear report with `pass`, `warn`, `fail`, and approval-required items rather than silently rewriting SEO-sensitive settings.

## Internal Content Graph

Current state: the plugin has site context, content inventory, recent updates, search/fetch tools, and SEO plugin detection. It does not yet expose a true graph of content nodes, links, backlinks, orphan pages, hubs, and related-content candidates.

Needed graph primitives:

- Nodes: posts, pages, custom post types, taxonomies, menu items, media, reusable patterns, and template parts.
- Edges: existing internal links, menu relationships, parent/child page relationships, taxonomy membership, embeds, media usage, and related content suggestions.
- Signals: title, slug, excerpt, headings, categories, tags, modified date, status, word count, current anchors, inbound link count, outbound link count, and SEO metadata.
- Actions: suggest links, preview link diffs, apply approved links, detect broken internal links, identify orphan content, and recommend hub pages.

Proposed MCP/REST tools:

- `wp_get_content_graph`
- `wp_suggest_internal_links`
- `wp_apply_internal_links`
- `wp_find_orphan_content`
- `wp_validate_internal_links`

Agents should never invent internal URLs. They should choose links from the graph, preserve existing user-authored links, avoid repeated anchors, and return a diff before applying changes.

## Design System Recipes

Initial recipes exposed by the endpoint:

- `page_hero`
- `feature_grid`
- `proof_band`
- `faq`
- `cta_band`

Next recipes to add:

- `comparison_table`
- `pricing_tiers`
- `case_study`
- `resource_index`
- `contact_section`
- `commerce_product_story`

## Sprint Plan

### Sprint 7 - Gutenberg Agent Design System

Goal: make native WordPress/Gutenberg the default page-building surface for free users and agents.

Issues to create or track:

- #273 Add block parse/serialize MCP tools. Status: implemented in first slice.
- #272 Add agent-facing design-system endpoint. Status: implemented in first slice.
- #274 Add local WordPress REST tests for block parse, serialize, save, and read-back.
- #275 Add pattern/template-part management tools.
- Add reusable design memory: approved sections, brand tokens, content tone, and layout archetypes.
- #276 Add compact router actions for `page.build`, `page.section.update`, `pattern.create`, and `template_part.update`.
- #279 Enforce block-native Gutenberg guardrails for agent edits.
- #278 Add SEO-safe Gutenberg publishing workflow.
- #281 Add section-level Gutenberg diff, patch, and rollback.
- #283 Add internal content graph for agent link suggestions.
- #282 Add internal link validation to Gutenberg publishing checks.
- #284 Add search and AI crawler visibility audit.
- #285 Add structured data recommendation and validation workflow.
- #286 Add content quality and entity coverage audit for AI search.
- #287 Add sitemap freshness and IndexNow workflow.
- #288 Add page experience and media SEO checks for Gutenberg pages.
- #289 Add Search Console and webmaster verification notes.
- Add admin documentation that explains Gutenberg-first workflows without exposing internal MCP complexity.

### Sprint 8 - SEO Intelligence Foundation

Goal: build a WordPress-native technical SEO audit, issue model, structured data inventory, media/page-experience checks, and internal link graph.

Backlog:

- #291 Build technical SEO site audit engine.
- #296 Build SEO issue model and scoring system.
- #290 Build internal link graph with PageRank-style signals.
- #293 Build structured data inventory and validator.
- #294 Build page experience and media audit engine.

### Sprint 9 - Keyword And Content Strategy

Goal: move from fixing SEO issues to planning content opportunities and briefs.

Backlog:

- #297 Build keyword and topic inventory from site content.
- #298 Add content gap and opportunity workflow.
- #299 Add keyword cannibalization and duplicate intent detection.
- #300 Add agent-safe SEO content brief generator.
- #301 Add competitor and SERP import interface.
- #302 Add topical cluster and hub page planner.

### Sprint 10 - SEO Monitoring And Reporting

Goal: track SEO health over time and turn findings into scheduled tasks.

Backlog:

- #303 Add SEO crawl history and trend storage.
- #304 Add Search Console/Bing data import and reporting hooks.
- #305 Add SEO dashboard and scheduled recommendations.
- #306 Add rank tracking placeholder and third-party import abstraction.
- #308 Add AI search visibility and citation readiness report.
- #307 Add approval-safe SEO autofix workflows.

## Guardrails

- Keep these tools in the free build. They make WP.org useful without requiring Pro.
- Do not render arbitrary dynamic blocks in the first slice. Parsing and serialization are safer than server-side render previews.
- Do not remove Elementor support. Elementor remains valuable for existing sites, but Gutenberg should be the default native path.
- Keep legacy direct MCP tools while the compact router is introduced.
- Prefer block patterns and template parts over duplicated markup for repeated sections.
- Treat raw HTML, inline JavaScript, and whole-page classic blocks as restricted output that requires explicit approval.
- Treat SEO checks as part of the save/publish workflow, not a separate afterthought.

## Local WordPress Validation

Minimum validation before release:

```bash
php -l includes/api/class-spai-rest-blocks.php
php -l includes/mcp/class-spai-mcp-free-tools.php
```

Then in local WordPress:

1. Activate the free build.
2. Create a draft page.
3. Call `wp_get_block_design_system`.
4. Generate a simple hero/CTA block page.
5. Call `wp_parse_blocks`.
6. Call `wp_set_blocks`.
7. Open the editor and confirm the content is editable as native blocks.
8. Call `wp_get_blocks` and confirm the saved tree matches the intended structure.
