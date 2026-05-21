# SEO Intelligence Roadmap

## Product Frame

MCPWP should give agents enough SEO intelligence to plan, build, audit, and improve native WordPress content without turning the site into an opaque automation box.

The model is Semrush-like in breadth, but WordPress-native in execution:

- Use WordPress content, blocks, taxonomies, menus, media, revisions, and SEO plugin fields as the source of truth.
- Prefer audit reports, diffs, and approval gates before mutation.
- Avoid unsupported ranking promises.
- Avoid scraping search results from the plugin by default.
- Support imports and future provider integrations for Search Console, Bing Webmaster Tools, rank trackers, and keyword data.
- Keep free SEO foundations useful for WP.org users; reserve larger historical/reporting/provider workflows for Pro if needed.

## Sources And Market Pattern

SEO platforms commonly group work into:

- Technical site audit and crawl health.
- Internal linking and site structure.
- Keyword/topic research and gap analysis.
- Competitor/SERP imports or comparisons.
- Position/rank tracking.
- Backlink or authority analysis.
- On-page recommendations and content briefs.
- Reporting and trend history.

Google's current guidance still centers on crawlable, useful, well-organized content; descriptive links; indexability; structured data that matches visible content; and page experience. AI search visibility adds crawler policy and answer/citation readiness, but it does not replace normal SEO fundamentals.

## Sprint 8 - SEO Intelligence Foundation

Goal: build the local audit and graph foundation inside WordPress.

Issues:

- #291 Build technical SEO site audit engine.
- #296 Build SEO issue model and scoring system.
- #290 Build internal link graph with PageRank-style signals.
- #293 Build structured data inventory and validator.
- #294 Build page experience and media audit engine.

Expected MCP/REST surfaces:

- `wp_seo_audit_site`
- `wp_seo_audit_url`
- `wp_get_seo_issues` - implemented first options-backed slice for stored top issues.
- `wp_get_content_graph`
- `wp_get_internal_link_opportunities`
- `wp_validate_structured_data`
- `wp_audit_media_seo`
- `wp_audit_content_quality`

Audit categories:

- Crawlability and indexability.
- Canonical and redirect consistency.
- Robots meta and robots.txt.
- Sitemap inclusion and last modified signals.
- Broken internal links and orphan pages.
- Duplicate titles and descriptions.
- Heading structure and H1 policy.
- Image alt text, size, dimensions, lazy loading, and nearby context.
- Structured data presence and visible-content alignment.
- Page experience risk signals.

## Sprint 9 - Keyword And Content Strategy

Goal: move from fixing issues to planning content opportunities.

Issues:

- #297 Build keyword and topic inventory from site content.
- #298 Add content gap and opportunity workflow.
- #299 Add keyword cannibalization and duplicate intent detection.
- #300 Add agent-safe SEO content brief generator.
- #301 Add competitor and SERP import interface.
- #302 Add topical cluster and hub page planner.

Expected MCP/REST surfaces:

- `wp_get_topic_inventory`
- `wp_find_content_gaps`
- `wp_detect_keyword_cannibalization`
- `wp_generate_seo_brief`
- `wp_import_serp_data`
- `wp_plan_topic_cluster`

Strategy signals:

- Existing page titles, slugs, headings, excerpts, taxonomies, and SEO metadata.
- Internal link graph and hub/leaf structure.
- Search Console/Bing imports when provided.
- Manual keyword lists or competitor URL imports.
- Content age, freshness, thin sections, duplicate intent, and missed internal links.
- Entity coverage and answerability.

Brief output should include:

- Search intent.
- Target entities and related questions.
- Suggested Gutenberg section structure.
- Existing pages to link to and from.
- Title, meta description, slug, H1, and schema recommendations.
- Media needs and alt text guidance.
- Claims that require user-provided evidence.
- Approval gates before creation or rewrite.

## Sprint 10 - SEO Monitoring And Reporting

Goal: track SEO health over time and turn findings into scheduled tasks.

Issues:

- #303 Add SEO crawl history and trend storage.
- #304 Add Search Console/Bing data import and reporting hooks.
- #305 Add SEO dashboard and scheduled recommendations.
- #306 Add rank tracking placeholder and third-party import abstraction.
- #308 Add AI search visibility and citation readiness report.
- #307 Add approval-safe SEO autofix workflows.
- #309 Build human control room for approvals and SEO issues.
- #310 Add Apify trend and SERP import provider.
- #311 Add WooCommerce SEO intelligence workflows.
- #312 Add AI-first event hooks and outbound webhooks.
- #313 Add coherent site state snapshot for agents and Control Room.
- #314 Add Control Room event inbox and escalation rules.
- #315 Define deterministic agent playbook contracts.
- #316 Add content coherence scoring and recommendations.

Expected MCP/REST surfaces:

- `wp_get_seo_report`
- `wp_get_seo_trends` - implemented first provider-neutral report from imported search performance rows.
- `wp_import_search_performance` - implemented first explicit import surface for Search Console, Bing, rank tracker, or manual rows.
- `wp_import_rank_tracking`
- `wp_get_ai_visibility_report`
- `wp_run_seo_autofix_plan` - implemented first read-only approval-safe planner from stored SEO issues.
- `wp_get_event_schema`
- `wp_list_mcp_events`
- `wp_get_site_state`
- `wp_get_agent_playbook`
- `wp_get_content_coherence_report`

Event surfaces:

- WordPress developer hooks for approval, SEO, graph, content, and activity lifecycle events.
- Signed outbound webhooks for external agents, chat channels, automations, and dashboards.
- Delivery logs and retry state visible from wp-admin.
- Control Room links in payloads when a human decision is required.

AI-first content model:

- Treat WordPress as one coherent content system, not isolated posts and pages.
- Event payloads should identify the resource, related graph nodes, current risk level, approval state, SEO state, and recommended next action.
- Agents should react to events by reading current state and creating approval requests, not by blindly mutating content from a chat message.

#307 status: first implementation adds `Spai_SEO_Autofix`, REST `GET /site-pilot-ai/v1/seo/autofix-plan`, and MCP `wp_run_seo_autofix_plan`. The planner consumes stored open SEO issues and returns strategy, tool, playbook, next step, approval requirement, and guardrails. It deliberately never applies fixes directly; every action reports `can_auto_apply=false`.

#304 status: first implementation adds `Spai_Search_Performance`, REST `POST /site-pilot-ai/v1/seo/search-performance/import`, REST `GET /site-pilot-ai/v1/seo/search-performance`, MCP `wp_import_search_performance`, and MCP `wp_get_seo_trends`. This slice stores explicit Search Console/Bing/manual exports and reports top queries, top URLs, daily aggregates, provider mix, CTR, and average position. It does not fetch external APIs yet.

#311 status: first implementation adds `Spai_WooCommerce_SEO`, REST `GET /site-pilot-ai/v1/seo/woocommerce`, and MCP `wp_get_woocommerce_seo_report`. It is read-only and checks product description depth, short description coverage, category evidence, product image presence, SKU/price/stock signals, imported search performance, and approval-safe next steps. Commerce mutations remain outside this report.
- `wp_get_site_state` is now the compact first read before multi-step work.
- Playbooks should encode safe tool order, validation gates, approval gates, and rollback paths.
- Coherence scoring should translate graph/SEO/content data into customer-facing priorities.

Reporting should show:

- New, resolved, and persistent issues.
- URL groups by severity and business importance.
- Internal link opportunities.
- Content refresh opportunities.
- Structured data coverage.
- Search performance import trends.
- AI crawler and citation readiness.
- Recommended next actions with risk and approval requirements.
- A human control room that combines approvals, stored SEO issues, recent agent work, and rollback-ready changes.

## Guardrails

- Do not promise rankings.
- Do not silently rewrite SEO-sensitive settings.
- Do not inject schema that is unsupported by visible content.
- Do not invent internal links or external citations.
- Do not scrape SERPs by default from the plugin.
- Do not push raw HTML/JS to solve SEO layout problems.
- Every mutating fix needs a preview, diff, approval state, and rollback path.

## Free vs Pro Direction

Free should include enough SEO value to make the WP.org version credible:

- Site-level SEO triage through `wp_seo_audit_site`.
- Optional stored issue tracking through `wp_seo_audit_site(store=true)` and `wp_get_seo_issues`.
- Per-page Gutenberg SEO checks through `wp_validate_seo_readiness`.
- Crawlability/indexability checks.
- Basic weighted internal graph and orphan detection.
- Basic structured data inventory and recommendations through `wp_validate_structured_data`.
- Basic media/alt text checks through `wp_audit_media_seo`.
- Basic answerability/entity/trust checks through `wp_audit_content_quality`.

Pro can carry heavier workflows:

- Historical crawl storage.
- Scheduled audits and reports.
- Search Console/Bing imports.
- Keyword/topic inventory stored inside WordPress and mapped to posts, pages, products, categories, and clusters.
- Rank tracker/provider imports.
- Competitor/SERP import analysis, including optional Apify provider imports for trend and market evidence.
- Bulk autofix workflows.
- Advanced content gap and topical cluster planning.

WooCommerce direction: keep SEO as the main product priority, then apply it to WooCommerce products and categories through #311. Product/category audits should cover discoverability, schema, images, thin descriptions, duplicate intent, content-to-product internal links, and Search Console/Bing performance mapped to revenue pages. Commerce mutations such as prices, stock, and settings require explicit approval.
