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
- Add admin documentation that explains Gutenberg-first workflows without exposing internal MCP complexity.

### Sprint 8 - Site Editor and Global Styles

Goal: let agents work with block themes safely.

Backlog:

- Read active theme and block theme status.
- Inspect global styles and theme.json-derived settings.
- Update template parts with approval and rollback.
- Create draft templates or template part revisions, never destructive direct edits first.
- Add preview/diff output for theme-level changes.

### Sprint 9 - Advanced Block Intelligence

Goal: make Gutenberg edits deterministic and high quality.

Backlog:

- Block tree diff endpoint.
- Section-level patch endpoint.
- Pattern matching: identify hero, CTA, FAQ, feature grid from block trees.
- Accessibility checks for headings, alt text, button text, and landmark structure.
- Render-safe preview pipeline for dynamic blocks.

## Guardrails

- Keep these tools in the free build. They make WP.org useful without requiring Pro.
- Do not render arbitrary dynamic blocks in the first slice. Parsing and serialization are safer than server-side render previews.
- Do not remove Elementor support. Elementor remains valuable for existing sites, but Gutenberg should be the default native path.
- Keep legacy direct MCP tools while the compact router is introduced.
- Prefer block patterns and template parts over duplicated markup for repeated sections.

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
