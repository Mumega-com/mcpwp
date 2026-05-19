# Agent Workflows

## Product Frame

Mumega MCP is the WordPress execution layer for AI design and coding agents.

The buyer and approver is still human: a site owner, agency operator, developer, marketer, or store manager. The daily operator can be an agent running in Claude Code, Codex CLI or desktop, OpenClaw, Hermes, Claude Desktop, or any MCP-capable client.

The plugin should therefore be designed for two audiences:

- Humans need control, review, permissions, billing clarity, logs, rollback paths, and confidence that production WordPress will not be damaged.
- Agents need compact tools, deterministic schemas, site context, design memory, workflow handles, and precise errors.

## Design-Agent Loop

Tools like Claude Design, Google Stitch, Figma, screenshots, and local mockups can produce interface direction, but they do not safely operate WordPress by themselves. Mumega MCP should bridge that gap.

Recommended loop:

1. Human or agent uploads a design reference from Stitch, Claude Design, Figma, screenshot, or mockup.
2. Mumega MCP stores the design reference with source, intent, notes, constraints, and approval state.
3. Agent reads site context, theme data, existing pages, reusable parts, archetypes, and design references.
4. Agent creates or updates a draft page, product page, section, reusable part, or template.
5. Mumega MCP validates capability, tier, scope, schema, and safety policy.
6. Human reviews, previews, approves, publishes, or rolls back.
7. Successful sections can be saved as reusable parts or archetype improvements.

## Primary Agent Personas

| Agent | Expected Use |
|---|---|
| Claude Code | Site investigation, page-building plans, design-reference interpretation, multi-step WordPress edits. |
| Codex CLI/Desktop | Repo-aware plugin development, local package validation, code-driven WordPress operations. |
| OpenClaw | Autonomous site maintenance and repeatable content workflows through MCP. |
| Hermes | Orchestrated tasks across tools, content, CRM, commerce, and WordPress. |
| Claude Desktop/Web MCP | Lighter operator workflows: inspect site, create drafts, update content, run approved workflows. |

## Compact Agent Tools

The compact MCP router should expose a small surface that agents can learn once and use repeatedly:

- `wp_get_site_context`
- `wp_capabilities`
- `wp_search`
- `wp_get`
- `wp_create`
- `wp_update`
- `wp_delete`
- `wp_media`
- `wp_get_schema`
- `wp_run_workflow`

Design-specific workflow handles should sit behind `wp_run_workflow` instead of becoming many separate public tools:

- `build_from_design_reference`
- `create_landing_page_from_archetype`
- `create_product_page_from_archetype`
- `update_page_section_from_reference`
- `save_section_as_reusable_part`
- `seo_cleanup`
- `commerce_product_cleanup`

Example:

```json
{
  "workflow": "build_from_design_reference",
  "design_reference_id": 42,
  "target": {
    "type": "draft_page",
    "title": "Spring Campaign"
  },
  "archetype": "saas_landing_page",
  "approval": "draft_only"
}
```

## Data Objects

Minimum objects the router and capability registry should understand:

| Object | Purpose | Likely Tier |
|---|---|---|
| `site_context` | Brand, theme, plugins, content model, installed builders, safety settings. | Free |
| `design_reference` | Screenshot, Figma node, Stitch output, Claude Design output, or mockup plus notes. | Pro |
| `archetype` | Approved page/product structure with variables and constraints. | Pro |
| `part` | Reusable Elementor/block section with metadata. | Pro |
| `workflow_run` | Logged execution of a multi-step action. | Free for basic logs, Pro for advanced workflows |
| `approval` | Human gate before publish/destructive changes. | Free |

## Safety Rules

- Default to draft for generated or substantially rewritten content.
- Require explicit approval before publishing, deleting, changing menus, changing commerce data, or modifying theme-builder templates.
- Return deterministic errors that agents can recover from: `pro_required`, `approval_required`, `invalid_payload`, `unknown_action`, `provider_not_configured`, `insufficient_scope`.
- Log the compact action, resolved internal handler, actor, API key, tier decision, changed resource IDs, and rollback hint.
- Keep legacy tools available during migration, but make compact mode the default for new installs and WP.org packages.

## Product Value

The value is not "AI inside WordPress." The value is that an external AI agent can safely do real WordPress work while respecting the site's structure, tools, permissions, and production risk.

The painful jobs to optimize first:

- Fix a broken or stale page without hand-editing Elementor JSON.
- Turn a mockup or screenshot into a draft WordPress page.
- Create consistent landing pages from approved archetypes.
- Update product pages and SEO metadata without missing commerce details.
- Reuse proven sections instead of generating one-off layouts.
- Give agencies a repeatable operating layer across many client sites.
