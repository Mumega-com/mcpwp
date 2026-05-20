# SEO Intelligence Roadmap

## Product Frame

Mumega MCP should give agents enough SEO intelligence to plan, build, audit, and improve native WordPress content without turning the site into an opaque automation box.

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
- `wp_get_seo_issues`
- `wp_get_content_graph`
- `wp_get_internal_link_opportunities`
- `wp_validate_structured_data`
- `wp_audit_media_seo`

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

Expected MCP/REST surfaces:

- `wp_get_seo_report`
- `wp_get_seo_trends`
- `wp_import_search_performance`
- `wp_import_rank_tracking`
- `wp_get_ai_visibility_report`
- `wp_run_seo_autofix_plan`

Reporting should show:

- New, resolved, and persistent issues.
- URL groups by severity and business importance.
- Internal link opportunities.
- Content refresh opportunities.
- Structured data coverage.
- Search performance import trends.
- AI crawler and citation readiness.
- Recommended next actions with risk and approval requirements.

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

- Per-page Gutenberg SEO checks.
- Crawlability/indexability checks.
- Basic weighted internal graph and orphan detection.
- Basic structured data recommendations.
- Basic media/alt text checks.

Pro can carry heavier workflows:

- Historical crawl storage.
- Scheduled audits and reports.
- Search Console/Bing imports.
- Rank tracker/provider imports.
- Competitor/SERP import analysis.
- Bulk autofix workflows.
- Advanced content gap and topical cluster planning.
